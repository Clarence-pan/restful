<?php


namespace Clarence\Restful\Curl;

use Clarence\Restful\TimeoutException;

class CurlMulti
{
    protected $handle;
    protected $curls = [];
    protected $options;

    protected $stillRunningCount;
    protected $execStatus;

    const DEFAULT_SELECT_LEAST_TIMEOUT = 0.001; // 默认select调用的超时时间，单位：秒

    /**
     * MultiCurl constructor.
     *
     * @param array $options curl_multi的选项
     * @throws CurlMultiException
     */
    public function __construct($options=[])
    {
        $this->handle = curl_multi_init();
        if (!$this->handle){
            throw new CurlMultiException("Cannot init a curl_multi!");
        }

        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param Curl|string $uri
     * @param array       $options
     * @return Curl
     * @throws CurlMultiException
     */
    public function add($uri, $options=[])
    {
        if ($uri instanceof Curl){
            $curl = $uri;
        } else {
            $curl = new Curl($uri);
        }

        $curl->setOptArray($options);
        $this->curls[] = $curl;

        $errno = curl_multi_add_handle($this->handle, $curl->getHandle());
        if ($errno != 0){
            throw new CurlMultiException(curl_multi_strerror($errno), $errno);
        }

        return $curl;
    }

    /**
     * 执行cURL请求
     */
    public function exec()
    {
        do {
            $this->execStatus = curl_multi_exec($this->handle, $this->stillRunningCount);
        } while ($this->execStatus == CURLM_CALL_MULTI_PERFORM);
    }

    /**
     * 等待所有的请求完成或超时
     * Wait until all request finished or timeout
     * @param callable|null $contentDealer
     * @param int|float     $totalTimeoutInSeconds
     * @return $this
     * @throws \Clarence\Restful\Curl\CurlMultiException
     * @throws \Clarence\Restful\TimeoutException
     */
    public function waitAll($contentDealer=null, $totalTimeoutInSeconds=-1)
    {
        $startTime = microtime(true);
        $this->exec();
        $selectTimeoutInSeconds = $totalTimeoutInSeconds < 0 ? null : max($totalTimeoutInSeconds, self::DEFAULT_SELECT_LEAST_TIMEOUT);
        while ($this->stillRunningCount > 0 && $this->execStatus == CURLM_OK) {
            $selected = curl_multi_select($this->handle, $selectTimeoutInSeconds);
            if ($selected == -1) {
                usleep(1);
            } else if (!is_null($contentDealer)) {
                $this->getAvailableContents($contentDealer);
            }

            // check whether timeout
            if ($totalTimeoutInSeconds > 0) {
                $usedTime = microtime(true) - $startTime;
                throw new TimeoutException(sprintf("Timeout after %.6f seconds", $usedTime));
            } else if ($totalTimeoutInSeconds == 0){
                break;
            }

            $this->exec();
        }

        if (!is_null($contentDealer)){
            $this->getAvailableContents($contentDealer);
        }

        return $this;
    }

    /**
     * 等待到有一个请求返回了内容
     *
     * @param callable $contentDealer
     * @param int|float     $totalTimeoutInSeconds
     * @param float    $selectTimeoutInSeconds
     * @return $this
     * @throws \Clarence\Restful\Curl\CurlMultiException
     * @throws \Clarence\Restful\TimeoutException
     */
    public function waitFirst($contentDealer=null, $totalTimeoutInSeconds=-1)
    {
        $startTime = microtime(true);
        $this->exec();

        $selectTimeoutInSeconds = max($totalTimeoutInSeconds, self::DEFAULT_SELECT_LEAST_TIMEOUT);
        while ($this->stillRunningCount > 0 && $this->execStatus == CURLM_OK) {
            $selected = curl_multi_select($this->handle, $selectTimeoutInSeconds);
            if ($selected == -1) {
                usleep(1);
            } else {
                $got = $this->getAvailableContents($contentDealer, $maxCount=1);
                if ($got){
                    break;
                }
            }

            // check whether timeout
            if ($totalTimeoutInSeconds > 0) {
                $usedTime = microtime(true) - $startTime;
                throw new TimeoutException(sprintf("Timeout after %.6f seconds", $usedTime));
            } else if ($totalTimeoutInSeconds == 0){
                break;
            }

            $this->exec();
        }

        return $this;
    }

    /**
     * 尽量少地等待（以便进行其他操作）
     * @param int|float     $totalTimeoutInSeconds
     * @return $this
     * @throws \Clarence\Restful\TimeoutException
     */
    public function waitLeast($totalTimeoutInSeconds=-1)
    {
        $startTime = microtime(true);
        $this->exec();

        $selectTimeoutInSeconds = max($totalTimeoutInSeconds, self::DEFAULT_SELECT_LEAST_TIMEOUT);
        while ($this->stillRunningCount > 0 && $this->execStatus == CURLM_OK) {
            $selected = curl_multi_select($this->handle, $selectTimeoutInSeconds);
            if ($selected == -1) {
                usleep(1);
            } else {
                break;
            }

            // check whether timeout
            if ($totalTimeoutInSeconds > 0) {
                $usedTime = microtime(true) - $startTime;
                throw new TimeoutException(sprintf("Timeout after %.6f seconds", $usedTime));
            } else if ($totalTimeoutInSeconds == 0){
                break;
            }

            $this->exec();
        }

        return $this;
    }

    /**
     * @param callable $contentDealer
     * @prarm int $maxCount
     * @throws CurlMultiException
     * @return int 获得了多少个内容
     */
    public function getAvailableContents($contentDealer, $maxCount=null)
    {
        $got = 0;

        while ($done = curl_multi_info_read($this->handle)) {
            if (!$done['handle']){
                throw new CurlMultiException("Invalid read handle: ".var_export($done, true));
            }

            $got++;

            $curl = $this->findCurlByHandle($done['handle']);
            if (!$curl){
                throw new CurlMultiException("Unknown read handle: ".var_export($done, true));
            }

            if ($contentDealer && is_callable($contentDealer)){
                call_user_func_array($contentDealer, [$curl->getContent(), $curl]);
            }

            if (!is_null($maxCount) && $got >= $maxCount){
                break;
            }
        }

        return $got;
    }

    /**
     * 获取cURL返回的内容
     * @param callable $iterator
     */
    public function getContents(callable $iterator){
        foreach ($this->curls as $curl) {
            /**@var $curl Curl */
            call_user_func_array($iterator, [$curl->getContent(), $curl]);
        }
    }

    /**
     * @param resource $curlHandle
     * @return \Clarence\Restful\Curl\Curl|null
     */
    public function findCurlByHandle($curlHandle)
    {
        foreach ($this->curls as $curl) {
            /**@var $curl Curl */
            if ($curl->getHandle() == $curlHandle){
                return $curl;
            }
        }

        return null;
    }

    /**@param $curl Curl */
    public function remove($curl)
    {
        curl_multi_remove_handle($this->handle, $curl->getHandle());
    }

    public function close()
    {
        if (!$this->handle){
            return;
        }

        foreach ($this->curls as $curl) {
            /**@var $curl Curl */
            curl_multi_remove_handle($this->handle, $curl->getHandle());
        }

        curl_multi_close($this->handle);
        $this->handle = null;
    }

    public function getDefaultOptions()
    {
        return [
            CURLMOPT_MAXCONNECTS => 10, // 同时缓存的活跃链接的数量
            CURLMOPT_PIPELINING => 0, // 不启用管道模式,不复用HTTP连接
        ];
    }
}

class CurlMultiException extends \Exception
{
}


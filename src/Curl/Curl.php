<?php


namespace Clarence\Restful\Curl;

/**
 * Class Curl
 *
 * @package Curl
 */
class Curl
{
    protected $url;
    protected $handle;

    public function __construct($url)
    {
        $this->url = $url;
        $this->handle = curl_init($url);
        $this->setOptArray($this->getDefaultOptions());
    }

    public function __destruct()
    {
        $this->close();
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function close()
    {
        if ($this->handle) {
            curl_close($this->handle);
            $this->handle = null;
        }
    }

    public function setOpt($opt, $val = null)
    {
        if (is_array($opt)) {
            $this->setOptArray($opt);
        }

        if ($opt == CURLOPT_URL){
            $this->url = $val;
        }

        return curl_setopt($this->handle, $opt, $val);
    }

    public function setOptArray(array $options)
    {
        if (isset($options[CURLOPT_URL])){
            $this->url = $options[CURLOPT_URL];
        }

        return curl_setopt_array($this->handle, $options);
    }

    public function errno()
    {
        return curl_errno($this->handle);
    }

    public function error()
    {
        return curl_error($this->handle);
    }

    public function escape($str)
    {
        return curl_escape($this->handle, $str);
    }

    public function exec()
    {
        return curl_exec($this->handle);
    }

    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * 获取内容（这个curl执行后才能获取内容）
     * @return string|mixed
     */
    public function getContent()
    {
        return curl_multi_getcontent($this->handle);
    }

    public function detach()
    {
        $handle = $this->handle;
        $this->handle = null;
        return $handle;
    }

    public function getDefaultOptions()
    {
        return [
            CURLOPT_RETURNTRANSFER => true,
        ];
    }
}

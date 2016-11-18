<?php


namespace Clarence\Restful\Curl;


use Clarence\Restful\JsonDecodeException;
use Clarence\Restful\Request;
use Clarence\Restful\Response;

class CurlResponse implements Response
{
    /**
     * @var CurlRequest
     */
    protected $request;

    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Request $request
     */
    public function __construct(Request $request, Curl $curl)
    {
        $this->request = $request;
        $this->curl = $curl;
    }

    public function getRequestMethod()
    {
        return $this->request->getMethod();
    }

    public function getRequestFinalUrl()
    {
        return $this->curl->getUrl();
    }

    /**
     * 应答的内容
     *
     * @return mixed
     */
    public function content()
    {
        return $this->curl->getContent();
    }

    /**
     * 原始应答
     *
     * @return mixed
     */
    public function raw()
    {
        return $this->curl->getContent();
    }

    /**
     * 返回http状态码
     * @return int|false
     */
    public function httpCode()
    {
        $httpCode = $this->curl->info(CURLINFO_HTTP_CODE);
        return is_numeric($httpCode) ? intval($httpCode) : false;
    }

    public function errno()
    {
        return $this->curl->errno();
    }

    public function error()
    {
        return $this->curl->error();
    }

    public function debugInfo()
    {
        $info = $this->curl->info();
        $debugInfo = [];
        $debugInfo['response'] = $this->curl->getContent();
        $debugInfo['request'] = [
            'method' => $this->request->getMethod(),
            'uri' => $this->request->getUri(),
            'post_data' => $this->request->getPostData(),
        ];
        $debugInfo['errno'] = $this->curl->errno();
        $debugInfo['error'] = $this->curl->error();
        $debugInfo['ext_info'] = $info;
        return $debugInfo;
    }

    /**
     * 返回JSON解码后数据
     *
     * @param bool $assoc 是否返回关联数组，如果为false则返回stdClass
     * @return array|\StdClass
     * @throws JsonDecodeException
     */
    public function json($assoc = true)
    {
        $json = json_decode($this->content(), $assoc);
        if (!$json && json_last_error() != JSON_ERROR_NONE){
            throw new JsonDecodeException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }
}
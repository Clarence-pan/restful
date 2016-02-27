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

    /**
     * 应答的内容
     *
     * @return mixed
     */
    public function content()
    {
        return $this->raw();
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
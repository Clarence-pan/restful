<?php

namespace Clarence\Restful;

interface Request
{
    /**
     * @return string GET|POST|PUT|DELETE
     */
    public function getMethod();

    /**
     * @return string 获取URI(含QueryString)
     */
    public function getUri();

    /**
     * @return mixed POST的数据
     */
    public function getPostData();

    /**
     * 发送请求，返回一个应答
     * @return Response
     */
    public function send();
}
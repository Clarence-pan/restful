<?php

namespace Clarence\Restful;

/**
 * Interface Response
 *
 * @package Clarence\Restful
 */
interface Response
{
    /**
     * 应答的内容
     * @return mixed
     */
    public function content();

    /**
     * 原始应答
     * @return mixed
     */
    public function raw();

    /**
     * 返回JSON解码后数据
     * @param bool $assoc 是否返回关联数组，如果为false则返回stdClass
     * @return array|\StdClass
     */
    public function json($assoc=true);

}
<?php


namespace Clarence\Restful\Curl;

use Clarence\Restful\Request;

class AsyncCurlResponse extends CurlResponse
{
    /**
     * @var CurlMulti
     */
    protected $curlMulti;

    public function __construct(Request $request, Curl $curl, CurlMulti $curlMulti)
    {
        parent::__construct($request, $curl);
        $this->curlMulti = $curlMulti;
    }

    public function content()
    {
        return $this->raw();
    }

    public function raw()
    {
        $this->wait();
        return $this->curl->getContent();
    }

    public function wait()
    {
        $this->curlMulti->waitAll();
    }
}
<?php


namespace Clarence\Restful\Curl;


class AsyncCurlRequest extends CurlRequest
{
    public function send()
    {
        $curl = new Curl($this->uri);
        if (!isset($this->options[CURLOPT_HTTPHEADER])){
            $this->options[CURLOPT_HTTPHEADER] = [];
        }

        $this->options[CURLOPT_HTTPHEADER][] = 'Connection: close';

        $curl->setOptArray($this->options);
        $curl->setOptArray([
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
        ]);

        $curlMulti = new CurlMulti();
        $curlMulti->add($curl);
        $curlMulti->exec();
        $curlMulti->waitLeast();

        return new AsyncCurlResponse($this, $curl, $curlMulti);
    }
}
<?php


namespace Clarence\Restful\Curl;


use Clarence\Restful\Request;
use Clarence\Restful\Response;
use Clarence\Restful\RestClient;

class CurlRequest implements Request
{
    protected $method;
    protected $postData;
    protected $uri;
    protected $options;

    public function __construct($method, $uri, $data, $restClientOptions)
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->postData = $data;
        $this->options = $this->buildCurlOptions($method, $uri, $data, $restClientOptions);
    }

    /**
     * 发送请求，返回一个应答
     * @return Response
     */
    public function send()
    {
        $curl = new Curl($this->uri);
        $curl->setOptArray($this->options);
        $curl->exec();

        return new CurlResponse($this, $curl);
    }


    /**
     * @return string GET|POST|PUT|DELETE
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string 获取URI(含QueryString)
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return mixed POST的数据
     */
    public function getPostData()
    {
        return $this->postData;
    }

    protected function buildCurlOptions($method, $uri, $data, $restClientOptions)
    {
        $curlOptions = [
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => isset($restClientOptions[RestClient::OPT_TIMEOUT]) ? $restClientOptions[RestClient::OPT_TIMEOUT] : 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => !empty($restClientOptions[RestClient::OPT_MAX_REDIRS]),
            CURLOPT_MAXREDIRS => isset($restClientOptions[RestClient::OPT_MAX_REDIRS]) ? $restClientOptions[RestClient::OPT_MAX_REDIRS] : 0,
        ];

        $data = is_array($data) ? http_build_query($data) : $data;
        $dataLen = strlen($data);

        switch ($method) {
            case 'GET':
                $curlOptions[CURLOPT_POST] = false;
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $data;
                $curlOptions[CURLOPT_HTTPHEADER] = ['Content-Length: ' . $dataLen];
                break;
            default:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                $curlOptions[CURLOPT_POSTFIELDS] = $data;
                $curlOptions[CURLOPT_HTTPHEADER] = [
                    'Content-Length: ' . $dataLen,
                    'X-HTTP-Method-Override: ' . $method
                ];
                break;
        }

        return $curlOptions;
    }
}
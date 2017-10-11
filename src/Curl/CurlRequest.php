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

        // CURLOPT_FOLLOWLOCATION cannot be activated when an open_basedir is set
        if (ini_get('open_basedir')){
            unset($curlOptions[CURLOPT_FOLLOWLOCATION]);
        }

        switch ($restClientOptions[RestClient::OPT_ENCTYPE]) {
            // application/x-www-form-urlencoded	Default. All characters are encoded before sent (spaces are converted to "+" symbols, and special characters are converted to ASCII HEX values)
            case RestClient::ENCTYPE_FORM_URLENCODED:
                // content type is auto set.
                $data = (is_array($data) || is_object($data)) ? http_build_query($data) : $data;
                break;

            // multipart/form-data	No characters are encoded. This value is required when you are using forms that have a file upload control
            case RestClient::ENCTYPE_MULTIPART_FORMDATA:
                // don't set the content-type! curl() will do it.
                // or the user must do it.
                break;

            // text/plain -- Spaces are converted to "+" symbols, but no special characters are encoded
            case RestClient::ENCTYPE_TEXT_PLAIN:
                $contentType = 'text/plain';
                $data = str_replace(' ', '+', $data);

            // raw do nothing
            case RestClient::ENCTYPE_RAW:
                break;

            // encode as json
            case RestClient::ENCTYPE_JSON:
                $contentType = 'application/json';
                $data = (is_array($data) || is_object($data)) ? json_encode($data) : $data;
                break;
            default:
                throw new \RuntimeException('Unknown enctype when building curl options!');
        }

        $curlOptions[CURLOPT_HTTPHEADER] = [];
        switch ($method) {
            case 'GET':
                $curlOptions[CURLOPT_POST] = false;
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $data;
                if (is_string($data)){
                    $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($data);
                }
                break;
            default:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                $curlOptions[CURLOPT_POSTFIELDS] = $data;
                if (is_string($data)){
                    $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($data);
                }
                $curlOptions[CURLOPT_HTTPHEADER][] = 'X-HTTP-Method-Override: ' . $method;
                break;
        }

        // append extra headers
        $extraHeadersHasContentType = false;
        if (isset($restClientOptions[RestClient::OPT_EXTRA_HEADERS])) {
            foreach ($restClientOptions[RestClient::OPT_EXTRA_HEADERS] as $key => $item) {
                $keyIsNumeric = is_numeric($key);
                if ($keyIsNumeric){
                    $curlOptions[CURLOPT_HTTPHEADER][] = $item;
                } else if (is_array($item)){
                    foreach ($item as $subItem) {
                        $curlOptions[CURLOPT_HTTPHEADER][] = $key . ': ' . $subItem;
                    }
                } else {
                    $curlOptions[CURLOPT_HTTPHEADER][] = $key . ': ' . $item;
                }

                // find out if content-type exists
                if (!$extraHeadersHasContentType){
                    if ($keyIsNumeric){
                        $extraHeadersHasContentType = strlen($item) >= 13 && strncasecmp($item, 'content-type:', 13) === 0; // 13: strlen('content-type:')
                    } else {
                        $extraHeadersHasContentType = strcasecmp($key, 'content-type') === 0;
                    }
                }
            }

        }

        if (isset($contentType) && !$extraHeadersHasContentType){
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type: ' . $contentType;
        }


        if (empty($curlOptions[CURLOPT_HTTPHEADER])){
            unset($curlOptions[CURLOPT_HTTPHEADER]);
        }

        return $curlOptions;
    }
}
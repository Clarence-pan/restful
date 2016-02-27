<?php


namespace Clarence\Restful;

use \Clarence\Restful\Curl\CurlMulti;

/**
 * Class RestClient
 *
 * @package Clarence\Restful
 */
abstract class RestClient
{
    const OPT_ASYNC = 'async';
    const OPT_TIMEOUT = 'timeout';
    const OPT_RESPONSE_DECODER = 'responseDecoder';
    const OPT_BASE_URL = 'baseUrl';

    protected $options;

    /**
     * RestClient constructor.
     *
     * @param array $options
     */
    public function __construct($options=[])
    {
        $this->options = array_merge($this->getDefaultOptions(), $options);
    }

    /**
     * 发送 GET 请求
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return \Clarence\Restful\Response 返回的响应
     */
    public function get($uri, $data = [], $options = [])
    {
        return $this->request('GET', $uri, $data, $options);
    }

    /**
     * 发送 POST 请求
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return \Clarence\Restful\Response 返回的响应
     */
    public function post($uri, $data = [], $options = [])
    {
        return $this->request('POST', $uri, $data, $options);
    }

    /**
     * 发送 PUT 请求
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return \Clarence\Restful\Response 返回的响应
     */
    public function put($uri, $data = [], $options = [])
    {
        return $this->request('PUT', $uri, $data, $options);
    }

    /**
     * 发送 DELETE 请求
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return \Clarence\Restful\Response 返回的响应
     */
    public function delete($uri, $data = [], $options = [])
    {
        return $this->request('DELETE', $uri, $data, $options);
    }

    /**
     * 发送自定义类型的请求
     * @param string $method GET|POST|PUT|DELETE...
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return \Clarence\Restful\Response
     * @throws
     */
    public function request($method, $uri, $data = [], $options = [])
    {
        $method = strtoupper($method);
        list($uri, $data) = $this->sanitizeUriAndData($method, $uri, $data);

        $options = array_merge($this->options, $options);

        $request = $this->createRequest($method, $uri, $data, $options);

        return $request->send();
    }

    /**
     * @param string $method GET|POST|PUT|DELETE...
     * @param string $uri
     * @param array $data
     * @param array $options
     * @return Request
     */
    protected abstract function createRequest($method, $uri, $data, $options);

    /**
     * 获取选项
     * @param $optionKey
     * @return mixed
     */
    public function getOption($optionKey)
    {
        return $this->options[$optionKey];
    }

    /**
     * 设置选项
     * @param string $optionKey
     * @param mixed $optionValue
     * @return $this
     */
    public function setOption($optionKey, $optionValue)
    {
        $this->options[$optionKey] = $optionValue;
        return $this;
    }

    /**
     * 获取默认选项
     * @return array
     */
    public function getDefaultOptions()
    {
        return [
            self::OPT_ASYNC => false,
            self::OPT_TIMEOUT => 30, // 单位：秒
            self::OPT_RESPONSE_DECODER => null,
            self::OPT_BASE_URL => '',
        ];
    }

    /**
     * @param string $method
     * @param string $uri
     * @param mixed $data
     * @return array ($realUri, $realData)
     */
    protected function sanitizeUriAndData($method, $uri, $data)
    {
        $uri = rtrim($this->getOption(self::OPT_BASE_URL), '/') . '/' . ltrim($uri, '/');

        if ($method == 'GET' && !empty($data)) {
            $realUri = $uri . (strpos($uri, '?') !== false ? '&' : '?') . http_build_query($data);
            return [$realUri, []];
        } else {
            return [$uri, $data];
        }
    }
}

class RestClientException extends \Exception{}
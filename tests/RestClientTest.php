<?php

//  php -S localhost:8888 1>server/access.log 2>server/error.log

namespace Clarence\Restful\Tests;


use Clarence\Restful\Curl\CurlRestClient;
use Clarence\Restful\RestClient;
use Clarence\Restful\Request;
use Clarence\Restful\Response;

class RestClientTest extends \PHPUnit_Framework_TestCase
{
    const BASE_URL = 'http://localhost:8888/server';

//    public function setup()
//    {
//        $output = system('ps -Af | grep localhost:8888 | grep -v grep');
//        $columns = preg_split('/\s+/', $output);
//        $pid = $columns[1];
//        if ($pid) {
//            system('kill ' . $pid);
//        }
//
//        chdir(__DIR__);
//        system('sh -c \'nohup php -S localhost:8888 1>server/access.log 2>server/error.log &\'', $ret);
//        if ($ret != 0) {
//            throw new \Exception("failed to start test-server: ret=" . $ret);
//        }
//    }

    public function test_Sample()
    {
        $restClient = new CurlRestClient();

        $data = ['test' => 'test', 'hello' => 'world!', 'special' => '@ # $!%^&*(),./;\'"[]-='];

        $jsonResponse = $restClient->get('http://localhost:8888/server/echo-globals.php', $data)->json();
        $this->assertEquals($jsonResponse['_GET'], $data);

        $jsonResponse = $restClient->post('http://localhost:8888/server/echo-globals.php', $data)->json();
        $this->assertEquals($jsonResponse['_POST'], $data);

        $jsonResponse = $restClient->put('http://localhost:8888/server/echo-globals.php', $data)->json();
        $this->assertEquals($jsonResponse['_POST'], $data);

        $jsonResponse = $restClient->delete('http://localhost:8888/server/echo-globals.php', $data)->json();
        $this->assertEquals($jsonResponse['_POST'], $data);
    }

    protected function _test_request($method, $options = [])
    {
        $restClient = new CurlRestClient(array_merge([RestClient::OPT_BASE_URL => self::BASE_URL], $options));

        $data = ['test' => 'test', 'hello' => 'world!', 'special' => '@ # $!%^&*(),./;\'"[]-='];

        $response = $restClient->request($method, '/echo-globals.php', $data);
        $this->assertInstanceOf(Response::class, $response);

        $responseJson = $response->json();
        $this->assertEquals($method, $responseJson['_SERVER']['REQUEST_METHOD']);

        if ($method == 'GET') {
            $this->assertEquals($data, $responseJson['_GET']);
        } else {
            $this->assertEquals($data, $responseJson['_POST']);
        }
    }

    public function test_SyncGet()
    {
        $this->_test_request('GET');
    }

    public function test_SyncPost()
    {
        $this->_test_request('POST');
    }

    public function test_SyncPut()
    {
        $this->_test_request('PUT');
    }

    public function test_SyncDELETE()
    {
        $this->_test_request('DELETE');
    }

    public function test_ASyncGet()
    {
        $this->_test_request('GET', [RestClient::OPT_ASYNC => true]);
    }

    public function test_ASyncPost()
    {
        $this->_test_request('POST', [RestClient::OPT_ASYNC => true]);
    }

    public function test_ASyncPut()
    {
        $this->_test_request('PUT', [RestClient::OPT_ASYNC => true]);
    }

    public function test_ASyncDELETE()
    {
        $this->_test_request('DELETE', [RestClient::OPT_ASYNC => true]);
    }

    public function test_AsyncLongRequest()
    {
        $restClient = new CurlRestClient([RestClient::OPT_BASE_URL => self::BASE_URL]);
        $restClient->setOption(RestClient::OPT_ASYNC, true);

        $data = ['sleep' => 3];
        $startTime = microtime(true);
        $response = $restClient->get('/long-request.php', $data);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertLessThan(0.1, microtime(true) - $startTime, 'Async request should return almost in no time');

        $responseJson = $response->json();
        $this->assertGreaterThanOrEqual($data['sleep'], microtime(true), 'The long-request should return after <sleep>(s)');
        $this->assertEquals($data, $responseJson);
    }

    public function test_AsyncLeastRequest()
    {
        $restClient = new CurlRestClient([RestClient::OPT_BASE_URL => self::BASE_URL]);
        $restClient->setOption(RestClient::OPT_ASYNC, true);

        $data = ['sleep' => 3, 'testId' => 'test-' . time() . rand()];
        $startTime = microtime(true);
        $response = $restClient->get('/long-request.php', $data);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertLessThan(0.1, microtime(true) - $startTime, 'Async request should return almost in no time');

        sleep($data['sleep'] + 1);

        $longRequestLogs = array_map(function($line){
            return json_decode($line, true);
        }, file(__DIR__.'/server/long-request.log'));
        $matchedLogs = array_filter($longRequestLogs, function($log) use ($data){
            return isset($log['testId']) && $log['testId'] == $data['testId'];
        });

        $this->assertNotEmpty($matchedLogs);

        $matchedLogs = array_column($matchedLogs, null, 'type');
        $this->assertArrayHasKey('begin', $matchedLogs);
        $this->assertArrayHasKey('end', $matchedLogs);
    }

    public function test_AsyncLeastRequest_indirectly()
    {
        $restClient = new CurlRestClient([RestClient::OPT_BASE_URL => self::BASE_URL]);

        $data = ['sleep' => 3, 'testId' => 'test-' . time() . rand()];
        $startTime = microtime(true);
        $response = $restClient->get('/long-request-trigger.php', $data);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertLessThan(0.1, microtime(true) - $startTime, 'Async request should return almost in no time');

        sleep($data['sleep'] + 1);

        $longRequestLogs = array_map(function($line){
            return json_decode($line, true);
        }, file(__DIR__.'/server/long-request.log'));
        $matchedLogs = array_filter($longRequestLogs, function($log) use ($data){
            return isset($log['testId']) && $log['testId'] == $data['testId'];
        });

        $this->assertNotEmpty($matchedLogs);

        $matchedLogs = array_column($matchedLogs, null, 'type');
        $this->assertArrayHasKey('begin', $matchedLogs);
        $this->assertArrayHasKey('end', $matchedLogs);
    }
}
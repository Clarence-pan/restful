<?php

require __DIR__ . '/../../vendor/autoload.php';

use \Clarence\Restful\Curl\CurlRestClient;

$restClient = new CurlRestClient();
$restClient->setOption(CurlRestClient::OPT_ASYNC, true);
$restClient->setOption(CurlRestClient::OPT_BASE_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/server/');
$restClient->get('/long-request.php', $_GET);


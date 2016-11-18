<?php

date_default_timezone_set('Asia/Shanghai');
$logFile = __DIR__ . '/long-request.log';

$logData = array_merge([
    'type' => 'begin',
    'time' => date('Y-m-d H:i:s')
], $_GET);
file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);

sleep(isset($_GET['sleep']) ? $_GET['sleep'] : 1);

echo json_encode($_GET);

$logData = array_merge([
    'type' => 'end',
    'time' => date('Y-m-d H:i:s')
], $_GET);
file_put_contents($logFile, json_encode($logData) . PHP_EOL, FILE_APPEND);

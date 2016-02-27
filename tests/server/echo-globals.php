<?php

header('Content-Type: application/json');
session_start();

// As to PUT/DELETE requests, the $_POST global var will not be automatically built.
// So we build it by ourself.
// ref: http://www.lornajane.net/posts/2008/accessing-incoming-put-data-from-php
$input = file_get_contents('php://input');
if (empty($_POST)){
    parse_str($input, $_POST);
}

echo json_encode([
    'input' => $input,
    '_GET' => $_GET,
    '_POST' => $_POST,
    '_SERVER' => $_SERVER,
    '_COOKIE' => $_COOKIE,
    '_SESSION' => $_SESSION,
    '_ENV' => $_ENV,
]);

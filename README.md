# Restful

A RESTful client, especially for async requests.

# Install

## From Composer

```sh
composer require clarence/restful
```

## From github

```sh
git clone git@github.com:Clarence-pan/restful.git
```

# Usage

Talk is cheap. Show the code:

```php
use Clarence\Restful\Curl\CurlRestClient;

// Do a GET request
$data = ['test' => 'test', 'hello' => 'world!'];

$jsonResponse = $restClient->get('http://localhost:8888/server/echo-globals.php', $data)->json();
// then use the $jsonRespose....

```


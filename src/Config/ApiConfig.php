<?php

namespace Tabula17\Satelles\Utilis\Config;

use Swoole\Coroutine\Http\Client;

class ApiConfig extends ConnectionConfig
{
    public string $basePath = '/';
    public string $method = 'GET';
    public string $protocol = 'http';
    public array $headers = [
        "Accept" => "application/json",
        "Content-Type" => "application/json"
    ];
    public int $timeout = 30;

    public function httpClient(): Client
    {
        $client = new Client($this->protocol . '://' . $this->host . $this->basePath);
        $client->set([
            'timeout' => $this->timeout
        ]);
        $client->setHeaders($this->headers);
        return $client;

    }

}
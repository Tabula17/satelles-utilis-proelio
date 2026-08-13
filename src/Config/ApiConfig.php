<?php

namespace Tabula17\Satelles\Utilis\Config;

use Swoole\Coroutine\Http\Client;

class ApiConfig extends ConnectionConfig
{
    protected(set) string $basePath = '/';
    protected(set) string $method = 'GET';
    public string $protocol = 'http';
    protected(set) array $headers = [
        "Accept" => "application/json",
        "Content-Type" => "application/json"
    ];
    protected(set) array $apiPaths = [];
    protected(set) int $timeout = 30;

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
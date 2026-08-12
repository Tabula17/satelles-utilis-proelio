<?php

namespace Tabula17\Satelles\Utilis\Config;

use Swoole\Coroutine\Http\Client;

class ApiConfig extends ConnectionConfig
{
    public string $basePath = '/';
    public array $headers = [
        "Accept" => "application/json",
        "Content-Type" => "application/json"
    ];
    public int $timeout = 30;

    public function httpClient(): Client
    {
        $client = new Client($this->host . $this->basePath, $this->port);
        $client->set([
            'timeout' => $this->timeout
        ]);
        $client->setHeaders($this->headers);
        return $client;

    }

}
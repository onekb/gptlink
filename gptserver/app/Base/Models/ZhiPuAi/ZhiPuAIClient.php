<?php

namespace App\Base\Models\ZhiPuAi;

use Hyperf\HttpMessage\Server\Connection\SwooleConnection;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Swoole\Coroutine\Http2\Client;
use Swoole\Http2\Request;

class ZhiPuAIClient
{
    /**
     * @var Client
     */
    protected $client;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    public function exec(RequestInterface $request)
    {
        return $request->send($this);
    }

    /**
     * @return mixed|ResponseInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function response()
    {
        if (! isset($this->response)) {
            $response = app()->get(ResponseInterface::class);
            /* @var SwooleConnection $connect */

            $connect = $response->getConnection();
            $connect->setHeader('content-Type', 'text/event-stream');
            $connect->setHeader('access-control-allow-origin', '*');
            $connect->setHeader('vary', 'origin');
            $connect->setHeader('access-control-allow-methods', 'POST');
            $response->setConnection($connect);

            $this->response = $response;
        }

        return $this->response;
    }

    /**
     * @return $this
     */
    public function connect()
    {
        $this->getClient()->connect();

        return $this;
    }

    /**
     * @return $this
     */
    public function reconnect()
    {
        $this->close();
        sleep(1);
        $this->connect();

        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->getClient()->close();

        return $this;
    }

    /**
     * @param null|mixed $timeout
     * @return mixed
     */
    public function read($timeout = null)
    {
        return $this->getClient()->read($timeout);
    }

    /**
     * @return int
     */
    public function send(Request $request)
    {
        return $this->getClient()->send($request);
    }

    protected function getClient()
    {
        if (! $this->client) {
            $clientConfig = ['open.bigmodel.cn', 443, true];

            $this->client = new Client(...$clientConfig);

            $options = ['timeout' => -1];

            $this->client->set($options);
        }

        return $this->client;
    }
}

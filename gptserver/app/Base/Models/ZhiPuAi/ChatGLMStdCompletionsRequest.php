<?php

namespace App\Base\Models\ZhiPuAi;

use App\Base\Models\MessageInterface;
use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\AiChatConfigDto;
use Swoole\Http2\Request;

class ChatGLMStdCompletionsRequest extends Request implements RequestInterface, MessageInterface
{
    use MessageTrait;

    public $path = '/api/paas/v3/model-api/chatglm_std/sse-invoke';

    public $method = 'POST';

    public $headers;

    public $cookies;

    public $data = '';

    public $pipeline = false;

    /**
     * @var ChatDto
     */
    public $dto;

    public $debug;

    public $result;

    public function __construct(ChatDto $dto, AiChatConfigDto $config)
    {
        $this->dto = $dto;

        $this->data = json_encode($this->toRemoteData($config, $dto), JSON_UNESCAPED_UNICODE);
        $this->headers = [
            'Accept' => 'text/event-stream',
            'Content-Type' => 'application/json',
            'Authorization' => $config->getZhiPuAiKey(),
        ];
    }

    public function send(ZhiPuAIClient $client): mixed
    {
        $client->send($this);

        $payload = $this->read($client, 5);

        $text = '';

        $this->jsonDebug($payload->data);

        while (! empty($resultData = $payload->data)) {
            $matches = [];
            $id = [];

            preg_match_all("/data:(.*)\n\n/", $resultData, $matches);
            preg_match_all("/id:(.*)\n/", $resultData, $id);

            if (! isset($matches[1])) {
                continue;
            }

            if (isset($matches[1][0])) {
                $text .= $matches[1][0];
            }

            $result = [
                'id' => $id[1][0],
                'model' => 'chatglm-std',
                'messages' => $text,
                'created' => time(),
            ];

            // 用户中断请求会失败，所以做了处理
            if (! $client->response()->write(sprintf('%s%s%s', $this->dto->formatBefore(), json_encode($result, JSON_UNESCAPED_UNICODE), $this->dto->formatAfter()))) {
                $this->result = $result;
                return $this;
            }

            $payload = $this->read($client, 5, 0);
        }

        $client->close();

        $this->result = $result ?? [];

        return $this;
    }

    public function read(ZhiPuAIClient $client, int $timeout = null, int $retry = 3)
    {
        $payload = $client->read($timeout);

        if (! $payload && $retry > 0) {
            logger('exception')->info('重试发送请求', [
                'req' => $this->data,
                'retry' => $retry,
            ]);

            $client->reconnect()->send($this);

            return $this->read($client, $timeout, $retry - 1);
        }

        return $payload;
    }

    /**
     * 增加日志记录
     *
     * @param $data
     */
    public function jsonDebug($data)
    {
        json_decode($data, true);

        if (! json_last_error()) {
            $this->debug = $data;
        }
    }
}

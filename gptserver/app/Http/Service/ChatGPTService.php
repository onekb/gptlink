<?php

namespace App\Http\Service;

use App\Base\Models\OpenAi\ChatCompletionsRequest;
use App\Base\Models\OpenAi\OpenaiChatCompletionsRequest;
use App\Base\Models\OpenAi\OpenAIClient;
use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\AiChatConfigDto;
use App\Http\Service\Abstract\ChatAbstract;
use App\Model\Config;

class ChatGPTService extends ChatAbstract
{
    /**
     * 发送请求
     *
     * @param ChatDto $dto
     * @param $userId
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    public function exec(ChatDto $dto, $userId, string $model = '')
    {
        /* @var AiChatConfigDto $config */
        $config = Config::toDto(Config::AI_CHAT);

        // 发送请求
        $client = new OpenAIClient($config);

        $request = match ($config->channel) {
            AiChatConfigDto::OPENAI => new OpenaiChatCompletionsRequest($dto, $config, $model),
            default => new ChatCompletionsRequest($dto, $config),
        };

        /* @var ChatCompletionsRequest $result */
        $result = $client->exec($request);

        logger()->info('openai result', [
            'user_id' => $userId,
            'result' => $result->result,
            'request' => $result->data,
            'debug' => $result->debug,
            'class' => $request::class,
        ]);

        return [$result, $request];
    }
}

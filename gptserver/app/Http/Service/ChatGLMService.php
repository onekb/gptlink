<?php

namespace App\Http\Service;

use App\Base\Models\ZhiPuAi\ChatGLMCompletionsRequest;
use App\Base\Models\ZhiPuAi\ZhiPuAIClient;
use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\AiChatConfigDto;
use App\Http\Service\Abstract\ChatAbstract;
use App\Model\Config;

class ChatGLMService extends ChatAbstract
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

        $client = new ZhiPuAIClient();

        $request = new ChatGLMCompletionsRequest($dto, $config, $model);

        /* @var ChatGLMCompletionsRequest $result */
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

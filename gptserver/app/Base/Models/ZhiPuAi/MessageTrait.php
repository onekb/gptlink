<?php

namespace App\Base\Models\ZhiPuAi;

use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\AiChatConfigDto;
use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

trait MessageTrait
{
    public function toRemoteData(AiChatConfigDto $config, ChatDto $dto)
    {
        return [
            'temperature' => $dto->getItem('temperature', 0.95),
            'top_p' => $dto->getItem('top_p', 0.7),
            'incremental' => true,
            'prompt' => $this->getMessage($config, $dto),
        ];
    }

    public function getMessage(AiChatConfigDto $config, ChatDto $dto)
    {
        $messages = [];

        $system = $dto->getItem('system') ?: $config->default_system_prompt;
        $systemTokens = $system ? app()->get(Gpt3Tokenizer::class)->count($system) : 0;

        $this->getLastMessages($config, $dto->getItem('last_id'), $messages, $systemTokens);

        $messages = array_reverse($messages);

        $messages[] = ['role' => 'user', 'content' => $dto->getItem('message')];

        if ($system) {
            array_unshift($messages, ['role' => 'system', 'content' => $system]);
        }

        return $messages;
    }

    public function getLastMessages(AiChatConfigDto $configDto, $lastId, array &$messages = [], int $totalTokens = 0, int $count = 8)
    {
        if (! $lastId || ! $message = cache()->get('chat-' . $lastId)) {
            return;
        }

        if ($count-- <= 0) {
            return;
        }

        $totalTokens += ($message['tokens'] ?: 0);

        $maxTokens = bcsub(
            (string) $configDto->openai_tokens,
            (string) $configDto->openai_response_tokens
        );

        if ($totalTokens >= $maxTokens) {
            return;
        }

        $messages[] = ['role' => 'assistant', 'content' => $message['result']];
        $messages[] = ['role' => 'user', 'content' => $message['message']];

        if ($message['last_id']) {
            $this->getLastMessages($configDto, $message['last_id'], $messages, $totalTokens, $count);
        }
    }
}

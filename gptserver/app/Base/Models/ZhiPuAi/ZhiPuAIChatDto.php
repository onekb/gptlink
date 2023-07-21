<?php

namespace App\Base\Models\ZhiPuAi;

use Gioni06\Gpt3Tokenizer\Gpt3Tokenizer;

class ZhiPuAIChatDto
{
    public function toRemoteData()
    {
        return [
            'temperature' => 0.95,
            'top_p' => 0.7,
            'incremental' => true,
            'prompt' => $this->getMessage(),
        ];
    }

    /**
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMessage()
    {
        $messages = [];

        $system = $this->getItem('system') ?: $this->config->default_system_prompt;
        $systemTokens = $system ? app()->get(Gpt3Tokenizer::class)->count($system) : 0;

        $this->getLastMessages($this->config, $this->getItem('last_id'), $messages, $systemTokens);

        $messages = array_reverse($messages);

        $messages[] = ['role' => 'user', 'content' => $this->getItem('message')];

        if ($system) {
            array_unshift($messages, ['role' => 'system', 'content' => $system]);
        }

        return $messages;
    }
}

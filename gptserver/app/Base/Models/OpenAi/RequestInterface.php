<?php

namespace App\Base\Models\OpenAi;

interface RequestInterface
{
    /**
     * @param OpenAIClient $client
     * @return mixed
     */
    public function send(OpenAIClient $client): mixed;
}

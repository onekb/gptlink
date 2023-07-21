<?php

namespace App\Base\Models\ZhiPuAi;

use App\Base\ZhiPuAi\OpenAIClient;

interface RequestInterface
{
    /**
     * @param OpenAIClient $client
     * @return mixed
     */
    public function send(ZhiPuAIClient $client): mixed;
}

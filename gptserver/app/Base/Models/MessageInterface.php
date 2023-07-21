<?php

namespace App\Base\Models;

use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\AiChatConfigDto;

interface MessageInterface
{
    public function toRemoteData(AiChatConfigDto $config, ChatDto $dto);

    public function getMessage(AiChatConfigDto $config, ChatDto $dto);
}

<?php

namespace App\Http\Service\Abstract;

use App\Base\Models\OpenAi\ChatCompletionsRequest;
use App\Http\Dto\ChatDto;
use App\Job\MemberConsumptionJob;
use App\Job\UserChatLogRecordJob;
use Psr\SimpleCache\InvalidArgumentException;

abstract class ChatAbstract
{
    /**
     * 分块返回
     *
     * @param mixed $userId
     * @param ChatDto $dto
     * @throws InvalidArgumentException
     */
    public function chatProcess($userId, ChatDto $dto)
    {
        [$result, $request] = $this->exec($dto, $userId);

        // 如果没有正常返回，不进行扣费与记录
        if ($result->result) {
            if (! $request instanceof ChatCompletionsRequest) {
                $dto->cached($result->result['id'], $result->result['messages']);
            }

            asyncQueue(new MemberConsumptionJob($userId));
            asyncQueue(new UserChatLogRecordJob(
                $result->result['messages'],
                $result->result['id'],
                $dto,
                $userId,
                $cacheMessage['first_id'] ?? ''
            ));
        }
    }

    /**
     * 发送请求
     *
     * @param ChatDto $dto
     * @param $userId
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \Throwable
     */
    abstract public function exec(ChatDto $dto, $userId);
}

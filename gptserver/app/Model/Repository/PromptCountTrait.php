<?php

namespace App\Model\Repository;

use App\Model\PromptCount;

trait PromptCountTrait
{
    /**
     * 删除个人中心统计缓存
     *
     * @param int $memberId
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function deleteCache(int $memberId)
    {
        $key = sprintf('%s%s', PromptCount::USER_MODEL_CACHE_COUNT, $memberId);
        if (cache()->has($key)) {
            cache()->delete($key);
        }
    }
}

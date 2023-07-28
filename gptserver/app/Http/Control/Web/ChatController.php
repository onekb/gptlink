<?php

namespace App\Http\Control\Web;

use App\Exception\ErrCode;
use App\Exception\LogicException;
use App\Http\Dto\ChatDto;
use App\Http\Dto\Config\KeywordDto;
use App\Http\Request\ChatRequest;
use App\Http\Service\ChatGLMService;
use App\Http\Service\ChatGPTService;
use App\Job\GptModelUsesJob;
use App\Model\Config;
use App\Model\MemberPackage;
use App\Model\Prompt;
use Cblink\HyperfExt\BaseController;

class ChatController extends BaseController
{
    /**
     * 实时返回
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException|\Throwable
     */
    public function process(ChatRequest $request)
    {
        // 查询是否允许访问
        throw_unless(
            MemberPackage::existsPackage($userId = auth()->id()),
            LogicException::class,
            ErrCode::MEMBER_INSUFFICIENT_BALANCE
        );

        // 关键词检测
        /* @var KeywordDto $keywordDto */
        $keywordDto = Config::toDto(Config::KEYWORD);
        if ($keywordDto->enable) {
            $keywords = json_decode($keywordDto->keywords, true);

            $pattern = '/' . implode('|', $keywords) . '/i';

            preg_match_all($pattern, $request->input('message'), $matches);

            throw_if($matches[0], LogicException::class, ErrCode::CHAT_CONTAINS_PROHIBITED_WORDS);
        }

        $system = null;

        if ($request->input('prompt_id') && $prompt = Prompt::query()->find($request->input('prompt_id'))) {
            $system = $prompt->system;
            asyncQueue(new GptModelUsesJob($prompt->id));
        }

        $chatDto = new ChatDto(array_merge($request->inputs(['message', 'last_id']), [
            'system' => $system,
            'stream' => true,
            'format_after' => "\n\ndata :",
        ]));

        // 选择模型
        $model = $request->input('model', '');
        $service = match ($model) {
            'GPT-4', 'GPT-3.5' => make(ChatGPTService::class),
            'ChatGLM-Std', 'ChatGLM-Lite', 'ChatGLM-Pro' => make(ChatGLMService::class),
            default => make(ChatGPTService::class),
        };

        // 数据量输出
        $service->chatProcess($userId, $chatDto, $model);

        return $this->success();
    }
}

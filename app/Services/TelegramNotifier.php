<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function __construct(
        protected ?string $token = null,
        protected ?string $chatId = null,
    ) {
        $this->token ??= config('telegram.bot_token');
        $this->chatId ??= config('telegram.chat_id');
    }

    public function configured(): bool
    {
        return filled($this->token) && filled($this->chatId);
    }

    public function send(string $message): bool
    {
        if (! $this->configured()) {
            return false;
        }

        $response = Http::timeout(15)->post(
            "https://api.telegram.org/bot{$this->token}/sendMessage",
            [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ],
        );

        if ($response->failed()) {
            Log::warning('Telegram sendMessage mislukt: '.$response->body());

            return false;
        }

        return true;
    }
}

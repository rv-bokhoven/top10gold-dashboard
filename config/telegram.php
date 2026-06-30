<?php

return [

    /*
    | Telegram-bot voor pushmeldingen (waarschuwingen + conversies).
    | Bot-token via @BotFather; chat-id = jouw chat of een groep.
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),

    'chat_id' => env('TELEGRAM_CHAT_ID'),

];

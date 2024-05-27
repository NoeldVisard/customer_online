<?php

namespace App\Service;

use TelegramBot\Api\BotApi;

class CustomBotApi extends BotApi
{
    protected function executeCurl(array $params): string
    {
        $ch = curl_init();
        curl_setopt_array($ch, $params);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Disable host verification
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \TelegramBot\Api\HttpException(curl_error($ch), curl_errno($ch));
        }
        curl_close($ch);

        return $response;
    }
}

<?php

namespace App\Controller;

use App\Enum\TelegramEnum;
use App\Service\CustomBotApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramController extends AbstractController
{
    private $telegram;

    public function __construct(CustomBotApi $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     */
    #[Route('/telegram/webhook', name: 'telegram_webhook')]
    public function webhook(Request $request): Response
    {
        $update = json_decode($request->getContent(), true);
        if (!is_array($update)) {
            return new Response('Invalid updates format: ' . print_r($update, true), Response::HTTP_BAD_REQUEST);
        }

        if (!isset($update['message']) && !isset($update['callback_query'])) {
            return new Response('No message parameter in updates: ' . print_r($update, true), Response::HTTP_BAD_REQUEST);
        }

        $telegramScenery = TelegramEnum::ERROR;
        if (isset($update['message'])) {
            $telegramScenery = TelegramEnum::USER_MESSAGE;
            $message = $update['message'];
        } elseif (isset($update['callback_query'])) {
            $telegramScenery = TelegramEnum::USER_CALLBACK;
            $message = $update['callback_query']['data'];
        }

        if ($telegramScenery === TelegramEnum::USER_MESSAGE) {
            switch ($message['text']) {
                case '/start':
                    $keyboard = new InlineKeyboardMarkup([
                        [['text' => 'Записаться к специалисту', 'callback_data' => 'openAppointment']],
                        [['text' => 'Я специалист', 'callback_data' => 'login']],
                    ]);

                    $this->telegram->sendMessage($message['chat']['id'], 'Добро пожаловать! Выберите опцию:', null, false, null, $keyboard);
                    break;
                // заглушка для презы
                case 'Сколько времени у меня есть одеться перед съёмкой?':
                    $this->telegram->sendMessage($message['chat']['id'], 'У вас есть 5 минут на сборы перед съёмкой. Но если вам нужно больше времени, сообщите об этом заранее', null, false, null, null);
                    break;
                case 'Сколько комнат в студии?':
                    $this->telegram->sendMessage($message['chat']['id'], 'В студии две комнаты: в первой стены белого цвета, во второй одна стена синяя, другая белая, третья стена белая, с двумя окнами, к четвёртой стене прикреплены цветные рулоны, благодаря которым можно менять цвет фона', null, false, null, null);
                    break;
                case 'Есть ли дополнительный источник света?':
                    $this->telegram->sendMessage($message['chat']['id'], 'Искусственный свет прилагается, включён в стоимость', null, false, null, null);
                    break;
                default:
                    $this->telegram->sendMessage($message['chat']['id'], 'Я не совсем понял', null, false, null, null);
                    break;
                // заглушка для презы
            }
        }

        if ($telegramScenery === TelegramEnum::USER_CALLBACK) {
            $callbackQuery = $update['callback_query'];
            $data = $callbackQuery['data'];

            switch ($data) {
                case 'openAppointment':
                    $this->telegram->sendMessage($callbackQuery['message']['chat']['id'], 'You pressed ' . $callbackQuery['data']);
                    break;
                case 'login':
                    $this->telegram->sendMessage($callbackQuery['message']['chat']['id'], 'You pressed ' . $callbackQuery['data']);
                    break;
                default:
                    $keyboard = new InlineKeyboardMarkup([
                        [['text' => 'Записаться к специалисту', 'callback_data' => 'openAppointment']],
                        [['text' => 'Я специалист', 'callback_data' => 'login']],
                    ]);

                    $this->telegram->sendMessage($message['chat']['id'], 'Добро пожаловать! Выберите опцию:', null, false, null, $keyboard);
            }
        }

        return new Response('OK');
    }
}
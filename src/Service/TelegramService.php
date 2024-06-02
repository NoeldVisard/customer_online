<?php

namespace App\Service;

use App\Repository\TelegramResponseRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramService
{
    private CustomBotApi $telegramBot;
    private TelegramResponseRepository $telegramResponseRepository;
    private $userStates = [];

    /**
     * @param CustomBotApi $botApi
     */
    public function __construct(CustomBotApi $botApi, TelegramResponseRepository $telegramResponseRepository)
    {
        $this->telegramBot = $botApi;
        $this->telegramResponseRepository = $telegramResponseRepository;
    }

    public function chooseSpecialist($callbackQuery): \TelegramBot\Api\Types\Message
    {
        return $this->telegramBot->sendMessage($callbackQuery['message']['chat']['id'], 'Напишите ID специалиста:');
    }

    public function handleMessage(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleUserMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function handleUserMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        $allPossibleMessages = $this->telegramResponseRepository->findAll();

        foreach ($allPossibleMessages as $possibleMessage) {
            if ($text === $possibleMessage->getAction()) {
                $responseData = $possibleMessage->getResponse();
                $keyboard = $this->createKeyboard($responseData);

                $this->telegramBot->sendMessage(
                    $chatId,
                    $this->getTextFromResponseData($responseData),
                    null,
                    false,
                    null,
                    $keyboard
                );
                return;
            } else {
                continue;
            }
        }

        $this->telegramBot->sendMessage($message['chat']['id'], 'Я не совсем понял');
        return;

        /*if ($text === '/start') {
            $keyboard = new InlineKeyboardMarkup([
                [['text' => 'Записаться к специалисту', 'callback_data' => 'openAppointment']],
                [['text' => 'Я специалист', 'callback_data' => 'login']],
            ]);

            $this->telegramBot->sendMessage(
                $chatId,
                'Добро пожаловать! Выберите опцию:',
                null,
                false,
                null,
                $keyboard
            );
        } else {
            // Handle other text messages
            switch ($text) {
                // заглушка для презы
                case 'Сколько времени у меня есть одеться перед съёмкой?':
                    $this->telegramBot->sendMessage($message['chat']['id'], 'У вас есть 5 минут на сборы перед съёмкой. Но если вам нужно больше времени, сообщите об этом заранее', null, false, null, null);
                    break;
                case 'Сколько комнат в студии?':
                    $this->telegramBot->sendMessage($message['chat']['id'], 'В студии две комнаты: в первой стены белого цвета, во второй одна стена синяя, другая белая, третья стена белая, с двумя окнами, к четвёртой стене прикреплены цветные рулоны, благодаря которым можно менять цвет фона', null, false, null, null);
                    break;
                case 'Есть ли дополнительный источник света?':
                    $this->telegramBot->sendMessage($message['chat']['id'], 'Искусственный свет прилагается, включён в стоимость', null, false, null, null);
                    break;
                default:
                    $this->telegramBot->sendMessage($message['chat']['id'], 'Я не совсем понял', null, false, null, null);
                    break;
                // заглушка для презы
            }
        }
        */
    }

    private function createKeyboard(array $buttons): InlineKeyboardMarkup
    {
        $inlineKeyboard = [];
        foreach ($buttons as $button) {
            if (isset($button['button_text'])) {
                $buttonData['text'] = $button['button_text'];
                $buttonData['callback_data'] = $button['callback_query'];
                $inlineKeyboard[] = [$buttonData];
            }
        }

        return new InlineKeyboardMarkup($inlineKeyboard);
    }

    private function getTextFromResponseData(array $responseData): string
    {
        foreach ($responseData as $response) {
            if (isset($response['text'])) {
                return $response['text'];
            }
        }

        throw new NotFoundHttpException();
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {

    }
}
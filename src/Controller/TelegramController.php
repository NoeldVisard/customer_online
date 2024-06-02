<?php

namespace App\Controller;

use App\Service\CustomBotApi;
use App\Service\TelegramService;
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
    private TelegramService $telegramService;

    public function __construct(CustomBotApi $telegram, TelegramService $telegramService)
    {
        $this->telegram = $telegram;
        $this->telegramService = $telegramService;
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

        try {
            $this->telegramService->handleMessage($update);
            return new Response('OK');
        } catch (\Exception $e) {
            return new Response('Error handling message: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }

/*
        if ($telegramScenery === TelegramEnum::USER_CALLBACK) {
            $callbackQuery = $update['callback_query'];
            $data = $callbackQuery['data'];

            switch ($data) {
                case 'openAppointment':
//                    $this->telegram->sendMessage($callbackQuery['message']['chat']['id'], 'You pressed ' . $callbackQuery['data']);
                    $this->telegramService->chooseSpecialist($callbackQuery);
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
        }*/

        return new Response('OK');
    }
}
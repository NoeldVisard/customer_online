<?php

namespace App\Service;

use App\Entity\TelegramResponse;
use App\Enum\TelegramStatusEnum;
use App\Repository\TelegramResponseRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramService
{
    private CustomBotApi $telegramBot;
    private TelegramResponseRepository $telegramResponseRepository;
    private TelegramStatusService $telegramStatusService;

    /**
     * @param CustomBotApi $botApi
     */
    public function __construct(
        CustomBotApi $botApi,
        TelegramResponseRepository $telegramResponseRepository,
        TelegramStatusService $telegramStatusService,
    )
    {
        $this->telegramBot = $botApi;
        $this->telegramResponseRepository = $telegramResponseRepository;
        $this->telegramStatusService = $telegramStatusService;
    }

    public function handleMessage(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleUserMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $callback = $callbackQuery['data'];
        $allPossibleMessages = $this->telegramResponseRepository->findAll();

        foreach ($allPossibleMessages as $possibleMessage) {
            if ($callback === $possibleMessage->getAction()) {
                $handler = $possibleMessage->getHandler();
                $this->$handler($possibleMessage, $chatId);

                return;
            }
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
                $handler = $possibleMessage->getHandler();
                $this->$handler($possibleMessage, $chatId);
                return;
            }
        }

        $this->telegramBot->sendMessage($message['chat']['id'], 'Я не совсем понял');
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

    /**
     * The method starts telegram customer_online
     * @param TelegramResponse $telegramResponse
     * @param int $chatId
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function start(TelegramResponse $telegramResponse, int $chatId): void
    {
        $responseData = $telegramResponse->getResponse();
        $keyboard = $this->createKeyboard($responseData);

        $this->telegramBot->sendMessage(
            $chatId,
            $this->getTextFromResponseData($responseData),
            null,
            false,
            null,
            $keyboard
        );
    }

    /**
     * The method suggests writing the specialist's ID
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function findSpecialist(TelegramResponse $telegramResponse, int $chatId): void
    {
        $this->telegramStatusService->writeTelegramStatus($chatId, TelegramStatusEnum::FIND_SPECIALIST);

        $responseData = $telegramResponse->getResponse();

        $this->telegramBot->sendMessage(
            $chatId,
            $this->getTextFromResponseData($responseData)
        );
    }
}
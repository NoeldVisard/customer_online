<?php

namespace App\Service;

use App\Entity\Service;
use App\Entity\TelegramResponse;
use App\Enum\TelegramStatusEnum;
use App\Repository\TelegramResponseRepository;
use DateTime;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TelegramBot\Api\Exception;
use TelegramBot\Api\InvalidArgumentException;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramService
{
    private CustomBotApi $telegramBot;
    private TelegramResponseRepository $telegramResponseRepository;
    private TelegramStatusService $telegramStatusService;
    private ServiceService $serviceService;
    private ClientService $clientService;
    private AppointmentService $appointmentService;
    private GigaChatService $gigaChatService;

    /**
     * @param CustomBotApi $botApi
     */
    public function __construct(
        CustomBotApi $botApi,
        TelegramResponseRepository $telegramResponseRepository,
        TelegramStatusService $telegramStatusService,
        ServiceService $serviceService,
        ClientService $clientService,
        AppointmentService $appointmentService,
        GigaChatService $gigaChatService,
    )
    {
        $this->telegramBot = $botApi;
        $this->telegramResponseRepository = $telegramResponseRepository;
        $this->telegramStatusService = $telegramStatusService;
        $this->serviceService = $serviceService;
        $this->clientService = $clientService;
        $this->appointmentService = $appointmentService;
        $this->gigaChatService = $gigaChatService;
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

        $status = ($this->getTelegramStatus($chatId))->getStatus();

        switch ($status) {
            case TelegramStatusEnum::CHOOSE_SERVICE:
                $this->showPossibleTime((int) $callback, $chatId);
                break;
            case TelegramStatusEnum::CHOOSE_TIME:
                $this->saveAppointment($callback, $chatId, $callbackQuery['from']);
                $this->askQuestions($callback, $chatId);
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

        $status = ($this->getTelegramStatus($chatId))->getStatus();

        switch ($status) {
            case TelegramStatusEnum::CHOOSE_SERVICE:
                $this->findSpecialistServices((int)$text, $chatId);
                return;
            case TelegramStatusEnum::ASK_QUESTIONS:
                $this->askQuestion($text, $chatId, $message['chat']['username']);
                return;
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

    private function getTelegramStatus(int $chatId)
    {
        return $this->telegramStatusService->getTelegramStatus($chatId);
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

    /**
     * The method find specialist services and send it all by buttons
     * @param int $specialistId
     * @param int $chatId
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function findSpecialistServices(int $specialistId, int $chatId): void
    {
        $this->telegramStatusService->writeTelegramStatus($chatId, TelegramStatusEnum::CHOOSE_SERVICE);

        /** @var Service[] $services */
        $services = $this->serviceService->getServices([
           'userId' => $specialistId,
        ]);

        $dataForKeyboard = [];
        foreach ($services as $service) {
            $dataForKeyboard[] = [
                'button_text' => $service->getName(),
                'callback_query' => $service->getId(),
            ];
        }
        $keyboard = $this->createKeyboard($dataForKeyboard);

        $this->telegramBot->sendMessage(
            $chatId,
            'Выберите услугу:',
            null,
            false,
            null,
            $keyboard
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function askQuestion(string $question, int $chatId, string $username): void
    {
        $client = $this->clientService->find([
            'phone' => $username,
        ]);
        $lastAppointment = $this->appointmentService->findLastAppointment([
            'clientId' => $client[0]->getId(),
        ]);
        $serviceDescription = $lastAppointment->getService()->getDescription();

        $answer = $this->gigaChatService->askQuestion($question, $serviceDescription);

        $this->telegramBot->sendMessage(
            $chatId,
            $answer
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function showPossibleTime(int $serviceId, $chatId): void
    {
        $this->telegramStatusService->writeTelegramStatus($chatId, TelegramStatusEnum::CHOOSE_TIME);

        // TD: use function createKeyboard and schedule of user. And to come up how to appoint correctly (by callback_data or by status)
        $keyboard = new InlineKeyboardMarkup([
            [['text' => '10:00', 'callback_data' => '10:00//' . $serviceId]],
            [['text' => '10:30', 'callback_data' => '10:30//' . $serviceId]],
            [['text' => '11:00', 'callback_data' => '11:00//' . $serviceId]],
            [['text' => '11:30', 'callback_data' => '11:30//' . $serviceId]],
            [['text' => '12:00', 'callback_data' => '12:00//' . $serviceId]],
            [['text' => '12:30', 'callback_data' => '12:30//' . $serviceId]],
            [['text' => '13:00', 'callback_data' => '13:00//' . $serviceId]],
            [['text' => '13:30', 'callback_data' => '13:30//' . $serviceId]],
            [['text' => '14:00', 'callback_data' => '14:00//' . $serviceId]],
            [['text' => '14:30', 'callback_data' => '14:30//' . $serviceId]],
            [['text' => '15:00', 'callback_data' => '15:00//' . $serviceId]],
            [['text' => '15:30', 'callback_data' => '15:30//' . $serviceId]],
            [['text' => '16:00', 'callback_data' => '16:00//' . $serviceId]],
            [['text' => '16:30', 'callback_data' => '16:30//' . $serviceId]],
            [['text' => '17:00', 'callback_data' => '17:00//' . $serviceId]],
            [['text' => '17:30', 'callback_data' => '17:30//' . $serviceId]],
        ]);

        $this->telegramBot->sendMessage(
            $chatId,
            'Выберите время для записи:',
            null,
            false,
            null,
            $keyboard
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    private function saveAppointment(string $appointmentData, int $chatId, array $from): void
    {
        $appointmentData = explode('//', $appointmentData);

        $currentDateTime = new DateTime();
        $nextDayDateTime = new DateTime($currentDateTime->format('Y-m-d') . ' ' . $appointmentData[0]);
        $nextDayDateTime->modify('+1 day');
        $appointmentDate = $nextDayDateTime->format('Y-m-d');
        $appointmentTime = $nextDayDateTime->format('H:i');
        // Here i don't need name & phone
        $this->serviceService->saveAppointment([
            'date' => $appointmentDate,
            'time' => $appointmentTime,
            'phone' => $from['username'],
            'name' => $from['first_name'] . ' ' . $from['last_name'],
            'service' => $appointmentData[1],
            'comment' => $this->serviceService->getServices(['id' => $appointmentData[1]])[0]->getName(),
        ]);

        $this->telegramBot->sendMessage(
            $chatId,
            'Вы успешно записаны!',
        );
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    private function askQuestions(string $appointmentData, int $chatId): void
    {
        $this->telegramStatusService->writeTelegramStatus($chatId, TelegramStatusEnum::ASK_QUESTIONS);

        $this->telegramBot->sendMessage(
            $chatId,
            'Если у вас есть вопросы, можете задать их в чате.',
        );
    }
}
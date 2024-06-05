<?php

namespace App\Service;

use App\Entity\TelegramStatus;
use App\Repository\TelegramStatusRepository;

class TelegramStatusService
{
    private TelegramStatusRepository $telegramStatusRepository;

    /**
     * @param TelegramStatusRepository $telegramStatusRepository
     */
    public function __construct(TelegramStatusRepository $telegramStatusRepository)
    {
        $this->telegramStatusRepository = $telegramStatusRepository;
    }

    public function writeTelegramStatus(int $chat, int $status): void
    {
        $telegramStatus = new TelegramStatus();
        $telegramStatus->setChat($chat);
        $telegramStatus->setStatus($status);

        $this->telegramStatusRepository->save($telegramStatus);
    }
}
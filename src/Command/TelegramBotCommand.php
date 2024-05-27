<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Inline\InlineKeyboardMarkup;

class TelegramBotCommand extends Command
{
    protected static $defaultName = 'app:telegram-bot';

    private BotApi $telegram;

    public function __construct(BotApi $telegram)
    {
        $this->telegram = $telegram;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Handle Telegram bot updates');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $updates = $this->telegram->getUpdates();

        foreach ($updates as $update) {
            $message = $update->getMessage();

            if ($message->getText() == '/start') {
                $keyboard = new InlineKeyboardMarkup([
                    [['text' => 'Button 1', 'callback_data' => 'button1']],
                    [['text' => 'Button 2', 'callback_data' => 'button2']]
                ]);

                $this->telegram->sendMessage($message->getChat()->getId(), 'Welcome! Choose an option:', null, false, null, $keyboard);
            }

            if ($callbackQuery = $update->getCallbackQuery()) {
                $data = $callbackQuery->getData();

                if ($data == 'button1') {
                    $this->telegram->sendMessage($callbackQuery->getMessage()->getChat()->getId(), 'You pressed Button 1!');
                } elseif ($data == 'button2') {
                    $this->telegram->sendMessage($callbackQuery->getMessage()->getChat()->getId(), 'You pressed Button 2!');
                }
            }
        }

        return Command::SUCCESS;
    }
}

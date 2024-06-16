<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'GetGigaChatTokenCommand',
    description: 'Command for get Giga Chat Token',
)]
class GetGigaChatTokenCommand extends Command
{
    protected static $defaultName = 'app:get-giga-chat-token';

    private $httpClient;
    private $params;
    private $authData;
    private $clientId;
    private $clientSecret;

    public function __construct($httpClient, $params, $authData, $clientId, $clientSecret)
    {
        parent::__construct();
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->authData = $authData;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Get Giga Chat Token')
//            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
//            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
            'RqUID' => '13b26ad8-a12d-44fb-989d-df0908e37e3c',
            'Authorization' => 'Basic YTZlOWJiZDEtZjMwMy00N2I4LTgwMDAtNWJmZDYzYmU1ZmVlOjAxOTA1Mjc3LTM1OGItNDUxMi1iMmFlLWQwMjViZTgwNzMwNw=='
        ];

        $body = 'scope=GIGACHAT_API_PERS';

        try {
            $response = $this->httpClient->request('POST', 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'headers' => $headers,
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent();

            if ($statusCode === 200) {
                $io->success('Request successful!');
                $io->writeln('Response content: ' . $content);
            } else {
                $io->error('Request failed with status code: ' . $statusCode);
                $io->writeln('Response content: ' . $content);
            }
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}

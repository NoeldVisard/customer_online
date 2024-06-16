<?php

namespace App\Service;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpClient;

class GigaChatService
{
    private HttpClientInterface $httpClient;

    public function __construct()
    {
        $this->httpClient = HttpClient::create();
    }

    public function askQuestion(string $question, string $context): string
    {
        $token = 'eyJjdHkiOiJqd3QiLCJlbmMiOiJBMjU2Q0JDLUhTNTEyIiwiYWxnIjoiUlNBLU9BRVAtMjU2In0.oCn_p155wN69lR0nLFQ2a13OwLkkHSTYgy5HjdggpjEz0k5BmQkDsIxERjP5YtARILiBY4pR05GM4yVA7W1WybVbm6Z2VBsrf5k490DyT5lnqeX30x8S2qGKVYchB_I5bv6wTHjTa-N1l2Mt7DQXdMGBYDDVqDRJEXrLr-LVQylihsXnoRdm-Gyh75Tl30wgaUvP12dnDLNvDsvja0by5jBKkjYw2hgOGZiGK6mKCpx0QDPAvxdrDdrvm-T5zTbI9QdpXJ2qjpT4pgP0UCAfpVzWlO9s758-UhsDSjWc9psYZ9Fs230XEdBiS0bULyg08Ijmatw5TK43kQIDS1nvHw.aZxG6mkY_KDi4KrQ8f-TsQ.h8uRno4ioxyEnpLRh3coCu_iuuODUSjl2aNfAY9T9707x1BLb5Y7QAXh_1YX2GBf-S7g-lUTO49s3kSIHoxdnSpTDj5da_yL2qqoZPvNhU9YBdVu2xUyCA_eQs5uJQWX6v93xNCSxeW9wQ7UuVp1AjrlQQ51Qux7wXA75A_g13SVvXCMDJ9t4lZ1h-cG1RcN5eh68ltiYmMkR2-no75dTuwyGXzgyb0QgHkE8MNKU6bBPbaYSuMubY19ZfTD4YD9WNxy97BnzXCX2MzXu8vESUTjJwMvv83345LsSq7TSRFzNuzBn7C2TzA0rNu8DaH9o8WMA6TEOc4NBHodwMJiZAXuZdNAkKYfyeN7X9TG67pMz3hd0C-kE4lnD590TphE6V_T9QgGEGaK7QRnZRkDj0K3eaTEC3MO2W2qquY1npZqjkPFX3azLJvg2-684vRdygCZ24FtRk8Nvz6i5PJKwZdZy2CVk1sRdWMDqJqDoAQMr0Ja8ZQ8OtD5wzNMJTHt3a1aYGXP21ypT-a8IzWHQwlnKWoDV7lWBkHeqHrqFm044tKasnSyP5bDx5C2zscQaWETWkiYUSTlJDIvF9kOD9YnFb3nqurt9EqMdoTJTLB4wEnhvkjciAg1MoF0rhLN6EM9f9g9BP77KHsKJhyixkMr6xVTXP2FH6QUprz0DvFX6rijb-T_FNrNXmHVFm7kym0-Y9_VUgqfOr_cUasqz0GEaCVk3skw5MItGgMzGsU._bME1WDPH40pacCKrrZ8zjvszg1hTtHWHFRclZ-iCTE';

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ];

        $body = [
            'model' => 'GigaChat',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Ты отвечаешь на вопрос клиента. Он только что записался на наши услуги, и теперь хочет уточнить некоторые моменты об услуге. Вот информация, которую ты можешь ему сообщить: ' . $context . '. Вот вопрос клиента: "' . $question . '"'
                ]
            ],
            'temperature' => 1.0,
            'top_p' => 0.1,
            'n' => 1,
            'stream' => false,
            'max_tokens' => 512,
            'repetition_penalty' => 1
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                'headers' => $headers,
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

        } catch (\Exception $e) {

        }

        $content = json_decode($content);

        return $content->choices[0]->message->content;
    }
}
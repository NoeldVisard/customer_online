<?php

namespace App\Service;

use App\Repository\ClientRepository;

class ClientService
{
    private ClientRepository $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function find(array $params): array
    {
        return $this->clientRepository->findBy($params);
    }
}
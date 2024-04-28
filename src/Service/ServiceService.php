<?php

namespace App\Service;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use Symfony\Bundle\SecurityBundle\Security;

class ServiceService {
    private Security $security;
    private ServiceRepository $serviceRepository;

    public function __construct(Security $security, ServiceRepository $serviceRepository)
    {
        $this->security = $security;
        $this->serviceRepository = $serviceRepository;
    }

    public function saveService($serviceData): void
    {
        $service = new Service();
        $service->setName($serviceData['name']);
        $service->setDuration($serviceData['duration']);
        $service->setCost($serviceData['cost']);
        $service->setUserId($this->security->getUser());

        $this->serviceRepository->save($service);
    }

    public function getSettingsData(): array
    {
        $settingsData = [];

        $settingsData['services'] = $this->serviceRepository->getServices([
            'userId' => $this->security->getUser()->getId()
        ]);

        return $settingsData;
    }

    public function getServices(array $params)
    {
        return $this->serviceRepository->getServices([
            'userId' => $params['userId']
        ]);
    }

    public function appointment(array $appointmentData)
    {
    }
}
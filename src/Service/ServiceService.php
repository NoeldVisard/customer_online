<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\Client;
use App\Entity\Service;
use App\Repository\AppointmentRepository;
use App\Repository\ClientRepository;
use App\Repository\ServiceRepository;
use DateTimeImmutable;
use phpDocumentor\Reflection\Types\This;
use Symfony\Bundle\SecurityBundle\Security;

class ServiceService {
    private Security $security;
    private ServiceRepository $serviceRepository;
    private ClientRepository $clientRepository;
    private AppointmentRepository $appointmentRepository;

    public function __construct(
        Security $security,
        ServiceRepository $serviceRepository,
        ClientRepository $clientRepository,
        AppointmentRepository $appointmentRepository,
    )
    {
        $this->security = $security;
        $this->serviceRepository = $serviceRepository;
        $this->clientRepository = $clientRepository;
        $this->appointmentRepository = $appointmentRepository;
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

    public function saveAppointment(array $appointmentData): void
    {
        $appointmentData['date'] = $appointmentData['date'] . ' ' . $appointmentData['time'];
        $appointmentData['date'] = DateTimeImmutable::createFromFormat('Y-m-d H:i', $appointmentData['date']);
        unset($appointmentData['time']);

        if (!$this->clientRepository->isPhoneExists($appointmentData['phone'])) {
            $client = new Client();
            $client->setName($appointmentData['name']);
            $client->setPhone($appointmentData['phone']);
            $this->clientRepository->save($client);
        } else {
            $client = $this->clientRepository->findBy(['phone' => $appointmentData['phone']])[0];
        }

        $appointment = new Appointment();
        $service = $this->serviceRepository->find($appointmentData['service']);
        $appointment->setService($service);
        $appointment->setData($appointmentData['date']);
        $appointment->setClient($client);
        $appointment->setComment($appointmentData['comment']);

        $this->appointmentRepository->save($appointment);
    }
}
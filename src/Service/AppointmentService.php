<?php

namespace App\Service;

use App\Repository\AppointmentRepository;

class AppointmentService
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(
        AppointmentRepository $appointmentRepository,
    )
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function getAppointment($params): array
    {
        $appointments = $this->appointmentRepository->findBy($params);
        return array_map(function ($appointment) {
            return [
                'service' => $appointment->getService()->getName(),
                'client' => $appointment->getClient()->getName(),
                'comment' => $appointment->getComment(),
                'data' => $appointment->getData()->format('Y-m-d H:i'),
            ];
        }, $appointments);
    }


}
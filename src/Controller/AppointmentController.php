<?php

namespace App\Controller;

use App\Service\ServiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppointmentController extends AbstractController
{
    #[Route('/appointment/{userId}', name: 'app_appointment')]
    public function index(int $userId, Request $request, ServiceService $service): Response
    {
        if ($request->isMethod('POST')) {
            $service->appointment($request->request->all());
        }

        $services = $service->getServices(['userId' => $userId]);

        return $this->render('appointment/index.html.twig', [
            'services' => $services,
        ]);
    }
}

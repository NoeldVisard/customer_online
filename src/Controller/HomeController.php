<?php

namespace App\Controller;

use App\Service\AppointmentService;
use App\Service\ServiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    #[Route('/home', name: 'home')]
    public function homePage(
        Request $request,
        AppointmentService $appointmentService,
        ServiceService $serviceService,
        Security $security
    ): Response
    {
        $currentServices = $serviceService->getServices(['userId' => $security->getUser()->getId()]);
        $currentServiceIds = array_map(function ($service) {
            return $service->getId();
        }, $currentServices);

        $homeData['appointments'] = $appointmentService->getAppointment([
            'service' => $currentServiceIds
        ]);

        return $this->render('home/index.html.twig', [
            'data' => $homeData,
        ]);
    }

    #[Route('/settings', name: 'settings')]
    public function settings(
        Request $request,
        ServiceService $service
    ): Response
    {
        if ($request->isMethod('POST')) {
            $service->saveService($request->request->all());
            return $this->redirectToRoute('settings');
        }

        $settingsData = $service->getSettingsData($request);

        return $this->render('settings/settings.html.twig', [
            'settingsData' => $settingsData,
        ]);
    }


}
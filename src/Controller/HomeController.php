<?php

namespace App\Controller;

use App\Service\ServiceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{

    #[Route('/home', name: 'home')]
    public function homePage()
    {
        return new Response('hello');
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

        $settingsData = $service->getSettingsData();

        return $this->render('settings/settings.html.twig', [
            'settingsData' => $settingsData,
        ]);
    }


}
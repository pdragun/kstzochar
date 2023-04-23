<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventRoute;
use App\Service\Gpx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GpxController extends AbstractController
{

    #[Route('/gpx/{id}', name: 'gpx', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(EventRoute $eventRoute): Response {
        $gpx = Gpx::transform($eventRoute->getGpx());

        return new Response(
            $gpx->toXML(),
            200,
            ['Content-Type' => 'application/xml;charset=UTF-8'],
        );
    }
}

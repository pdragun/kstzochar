<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\EventRoute;
use App\Service\Gpx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GpxController extends AbstractController
{

    #[Route('/gpx/{id}', name: 'gpx', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(EventRoute $eventRoute): Response {
        if ($eventRoute->getGpx() === null) {
            throw $this->createNotFoundException();
        }

        // drafts and routes no longer used by any content stay private; admins can still check them
        if (!$this->isPublished($eventRoute) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException();
        }

        $gpx = Gpx::transform($eventRoute->getGpx());

        return new Response(
            $gpx->toXML(),
            200,
            ['Content-Type' => 'application/xml;charset=UTF-8'],
        );
    }

    /** Whether the route belongs to at least one published invitation or chronicle */
    private function isPublished(EventRoute $eventRoute): bool
    {
        return array_any(
            [...$eventRoute->getEventInvitations(), ...$eventRoute->getEventChronicles()],
            fn($content) => $content->getPublish(),
        );
    }
}

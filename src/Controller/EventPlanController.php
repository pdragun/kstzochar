<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Planned Event
 */
class EventPlanController extends AbstractController
{
    /**
     * Show list of years
     * @return Response Show list of years
     * @throws Exception
     */
    #[Route('/plan', name: 'plan', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        $maxYear = $eventRepository->findMaxStartYear();
        if ($maxYear === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('event_plan/showEvent.html.twig', ['maxYear' => $maxYear]);
    }

    /**
     * Show list of events per year
     * @return Response Show list of events
     */
    #[Route(
        '/plan/{year}',
        name: 'plan_show_by_Year',
        requirements: ['year' => '\d+'],
        methods: ['GET']
    )]
    public function showByYear(int $year, EventRepository $eventRepository): Response
    {
        $planYear = $eventRepository->getPreparedByYear($year);
        if(!$planYear) { // 404
            throw $this->createNotFoundException();
        }
        
        return $this->render('event_plan/showListByYear.html.twig', [
            'year' => $planYear,
            'yearInUrl' => $year,
        ]);
    }
}

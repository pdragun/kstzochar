<?php declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
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
     * 
     * @Route("/plan", name="plan")
     * 
     * @param App\Repository\EventRepository $eventRepository
     * @return Symfony\Component\HttpFoundation\Response Show list of years
     */
    public function index(EventRepository $eventRepository): Response
    {
        /** @var App\Entity\Event $maxYear */
        $maxYear = $eventRepository->findMaxStartYear();
        if(!$maxYear) { // 404
            throw $this->createNotFoundException();
        }

        return $this->render('event_plan/showEvent.html.twig', ['maxYear' => $maxYear]);
    }

    
    /**
     * Show list of events per year
     * 
     * @Route("/plan/{year}", name="plan_show_by_Year", requirements={"year"="\d+"})
     * 
     * @param int $year
     * @param App\Repository\EventRepository $eventRepository
     * @return Symfony\Component\HttpFoundation\Response Show list of events
     */
    public function showByYear(int $year, EventRepository $eventRepository): Response
    {
        /** @var App\Entity\Event[] $planYear */
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

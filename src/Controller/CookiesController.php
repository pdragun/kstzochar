<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CookiesController extends AbstractController
{
    /** Which cookies the site uses; with Google Analytics enabled also the consent settings */
    #[Route('/cookies', name: 'cookies', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('cookies/index.html.twig');
    }
}

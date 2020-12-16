<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractController
{
    /**
     * Show contact page
     * 
     * @Route("/kontakt", name="contact")
     * 
     * @return Symfony\Component\HttpFoundation\Response Show contact page
     */
    public function index(): Response
    {
        return $this->render('contact/index.html.twig');
    }
}

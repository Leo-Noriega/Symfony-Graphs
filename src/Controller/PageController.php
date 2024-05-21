<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    #[Route('/')]
    public function home(Request $request): Response
    {
        $search = $request->get('search');

        return $this->render('home.html.twig', ['search' => $search,]);
    }

}


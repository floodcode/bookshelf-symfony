<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
Use Symfony\Component\Routing\Annotation\Route;

class HomeController extends Controller
{

    /**
     * @Route("/")
     */
    public function index()
    {
        return $this->render('home/index.html.twig');
    }

}
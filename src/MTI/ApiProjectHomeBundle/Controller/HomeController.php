<?php

namespace MTI\ApiProjectHomeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class HomeController extends Controller
{
    public function indexAction()
    {
        return $this->render('MTIApiProjectHomeBundle:Home:index.html.twig');
    }
}

<?php

namespace MTI\HelloWorldBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdvertController extends Controller
{
    public function indexAction()
    {
        return $this->render('MTIHelloWorldBundle:Advert:index.html.twig', array('nom' => 'Pierre'));
    }
}

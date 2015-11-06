<?php

namespace MTI\HelloWorldBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('MTIHelloWorldBundle:Default:index.html.twig', array('name' => $name));
    }
}

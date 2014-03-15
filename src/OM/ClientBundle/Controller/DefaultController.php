<?php

namespace OM\ClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('OMClientBundle:Default:index.html.twig', array());
    }
}

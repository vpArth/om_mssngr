<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function okAction(Request $req)
    {
        return "ok";
    }
}

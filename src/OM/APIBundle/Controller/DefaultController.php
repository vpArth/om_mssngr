<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use OM\APIBundle\Helper\Request;

class DefaultController extends Controller
{
    public function okAction(Request $req)
    {
        return "ok";
    }
}

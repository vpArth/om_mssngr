<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use OM\APIBundle\Helper\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController implements ISignedController
{
    public function okAction(Request $req)
    {
        return "ok";
    }
}

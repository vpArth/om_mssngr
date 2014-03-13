<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use OM\APIBundle\Helper\Validation;

class AuthController extends Controller implements ISignedController
{
    public function okAction()
    {
        return "ok";
    }

    public function registerAction(Request $req)
    {
        $val = new Validation($this->get('validator'));
        $errors = $val->validate($req, array(
           'required' => array('username', 'email', 'password'),
            'email' => array('email')
        ));
        if ($errors) {
            throw new \Exception($errors/*, Validation::INVALID_PARAMS*/);
            //or may be better move this throw to Validation class...
        }
        return "ok";
    }
}

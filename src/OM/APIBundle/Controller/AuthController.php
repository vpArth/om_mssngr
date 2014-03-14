<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use OM\APIBundle\Helper\Request;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;

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
            throw new \Exception($errors, Validation::INVALID_PARAMS);
        }

        $user = new User();
        /** @var EncoderFactoryInterface $factory */
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        $user->setValues(array(
            'username' => $req->params->get('username'),
            'email' => $req->params->get('email'),
            'password' => $encoder->encodePassword($req->params->get('password'), $user->getSalt()),
            'isActive' => true
        ));

        $errors = $val->checkUniques($user, array('username', 'email'));
        if ($errors) {
            throw new \Exception($errors, Validation::UNIQUE_FAILED);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return "ok";
    }
}

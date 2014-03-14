<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;

use Doctrine\Common\Cache\Cache;

class AuthController extends Controller implements ISignedController
{
    public function okAction(Request $req)
    {
        if ($token = $req->params->get('token')) {
            /** @var Cache $cache */
            $cache = $this->get('aequasi_cache.instance.default');
            $key = "authToken:" . $token;
            if ($cache->contains($key)) {
                $userId= $cache->fetch($key);
                $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
                $user = $repo->find($userId);
                return "Hello, ".$user->getUsername();
            }
            return "Wrong token";
        }
        return "okay";
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
        return "User registered successfully";
    }

    public function loginAction(Request $req)
    {
        $val = new Validation($this->get('validator'));
        $errors = $val->validate($req, array(
            'required' => array('username', 'password')
        ));
        if ($errors) {
            throw new \Exception($errors, Validation::INVALID_PARAMS);
        }
        $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
        $user = $repo->findOneBy(array('username' => $req->params->get('username')));
        if (!$user) {
            throw new \Exception("User not found", 21001);
        }
        /** @var EncoderFactoryInterface $factory */
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder->isPasswordValid(
            $user->getPassword(),
            $req->params->get('password'),
            $user->getSalt()
        )) {
            throw new \Exception("Bad credentials", 21002);
        }
        /** @var Cache $cache */
        $cache = $this->get('aequasi_cache.instance.default');
        $token = uniqid();
        $key = "authToken:" . $token;
        $cache->save($key, $user->getId(), 24*3600);
        return array(
            "token" => $token
        );
    }
}

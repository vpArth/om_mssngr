<?php

namespace OM\APIBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;
use OM\APIBundle\Entity\UserRepository;

use Doctrine\Common\Cache\Cache;

class AuthController extends Controller implements ISignedController
{
    /**
     * @param Request $req
     * @param Controller $ctrlr
     * @param bool $update
     * @throws \Exception
     * @return User
     */
    public static function authorize(Request $req, Controller $ctrlr, $update = true)
    {
        if ($token = $req->params->get('token')) {
            /** @var Cache $cache */
            $cache = $ctrlr->get('aequasi_cache.instance.default');
            $key = "authToken:" . $token;
            if ($cache->contains($key)) {
                $userId= $cache->fetch($key);
                /** @var UserRepository $repo */
                $repo = $ctrlr->getDoctrine()->getRepository('OMAPIBundle:User');
                /** @var User $user */
                if (($user = $repo->find($userId)) && $user->getIsActive()) {
                    if ($update) {
                        $repo->updateLastLogin($user);
                    }
                    return $user;
                }
            }
            throw new \Exception("Wrong or expired token", Validation::BAD_TOKEN);
        }
        throw new \Exception("Not authorized", Validation::NOT_AUTHORIZED);
    }

    public function okAction(Request $req)
    {
        if ($token = $req->params->get('token')) {
            $user = AuthController::authorize($req, $this);
            return "Hello, " . $user->getUsername();
        }
        return "okay";
    }

    public function registerAction(Request $req)
    {
        $val = new Validation($this->get('validator'));
        $val->valParams($req, array(
           'required' => array('username', 'email', 'password'),
            'email' => array('email')
        ));

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
        $val->valParams($req, array(
            'required' => array('username', 'password')
        ));

        /** @var UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
        /** @var User $user */
        $user = $repo->findOneBy(array('username' => $req->params->get('username')));
        if (!$user) {
            throw new \Exception("User not found", Validation::USER_NOT_FOUND);
        }
        /** @var EncoderFactoryInterface $factory */
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder->isPasswordValid(
            $user->getPassword(),
            $req->params->get('password'),
            $user->getSalt()
        )) {
            throw new \Exception("Bad credentials", Validation::BAD_CREDENTIALS);
        }
        /** @var Cache $cache */
        $cache = $this->get('aequasi_cache.instance.default');
        $token = uniqid();
        $key = "authToken:" . $token;
        $cache->save($key, $user->getId(), 24*3600);
        $repo->updateLastLogin($user);
        return array(
            "token" => $token
        );
    }
}

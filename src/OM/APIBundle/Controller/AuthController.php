<?php

namespace OM\APIBundle\Controller;

use OM\APIBundle\Entity\UserModelManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;

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
                /** @var UserModelManager $userMM */
                $userMM = $ctrlr->get('omapi.user_model_manager');
                /** @var User $user */
                if (($user = $userMM->find($userId))) {
                    if ($update) {
                        $userMM->updateOnline($user);
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
            AuthController::authorize($req, $this);
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

        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');
        /** @var User $user */
        $user = $userMM->create();

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
        $userMM->save($user);
        return "User registered successfully";
    }

    public function loginAction(Request $req)
    {
        $val = new Validation($this->get('validator'));
        $val->valParams($req, array(
            'required' => array('username', 'password')
        ));

        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');

        /** @var User $user */
        if (!($user = $userMM->findBy(array('username' => $req->params->get('username'))))) {
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

        $token = $userMM->login($user);
        return array(
            "token" => $token
        );
    }

    public function logoutAction(Request $req)
    {
        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');

        $token = $req->params->has('token') ? $req->params->get('token') : false;
        if ($token) {
            $userMM->logout($token);
        }
        return "ok";
    }
}

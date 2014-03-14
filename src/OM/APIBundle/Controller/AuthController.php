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
    const NOT_AUTHORIZED = 21001;
    const BAD_TOKEN = 21002;
    const BAD_CREDENTIALS = 21003;
    const USER_NOT_FOUND = 21004;

    /**
     * @param Request $req
     * @param Controller $ctrlr
     * @throws \Exception
     * @return User
     */
    public static function authorize(Request $req, Controller $ctrlr)
    {
        if ($token = $req->params->get('token')) {
            /** @var Cache $cache */
            $cache = $ctrlr->get('aequasi_cache.instance.default');
            $key = "authToken:" . $token;
            if ($cache->contains($key)) {
                $userId= $cache->fetch($key);
                $repo = $ctrlr->getDoctrine()->getRepository('OMAPIBundle:User');
                /** @var User $user */
                if (($user = $repo->find($userId)) && $user->getIsActive()) {
                    return $user;
                }
            }
            throw new \Exception("Wrong or expired token", self::BAD_TOKEN);
        }
        throw new \Exception("Not authorized", self::NOT_AUTHORIZED);
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
        /** @var UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
        /** @var User $user */
        $user = $repo->findOneBy(array('username' => $req->params->get('username')));
        if (!$user) {
            throw new \Exception("User not found", self::USER_NOT_FOUND);
        }
        /** @var EncoderFactoryInterface $factory */
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if (!$encoder->isPasswordValid(
            $user->getPassword(),
            $req->params->get('password'),
            $user->getSalt()
        )) {
            throw new \Exception("Bad credentials", self::BAD_CREDENTIALS);
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

    public function userListAction(Request $req)
    {
        /** @var User $user */
        $user = AuthController::authorize($req, $this);
        /** @var UserRepository $repo */
        $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
        $params = array();
        $params['exclude_id'] = $user->getId();
        $params['fields'] = array('id', 'username', 'lastLogin');
        if ($req->params->has('page')) {
            $params['page'] = $req->params->get('page');
        }
        if ($req->params->has('size')) {
            $params['size'] = $req->params->get('size');
        }
        $list = $repo->userList($params);
        return $list;
    }

    public function profileAction(Request $req, $id)
    {
        $user = AuthController::authorize($req, $this);
        $repo = $this->getDoctrine()->getRepository('OMAPIBundle:User');
        $profile = $repo->getProfile($id, array(
            'self' => $user,
            'fields' => array('id', 'username', 'email', 'lastLogin')
        ));
        if (!$profile) {
            throw new \Exception("Requested user inactive or not exists", self::USER_NOT_FOUND);
        }
        return $profile;
    }
}

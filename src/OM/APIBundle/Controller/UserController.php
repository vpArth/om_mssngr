<?php

namespace OM\APIBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;
use OM\APIBundle\Entity\UserRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserController extends Controller implements ISignedController
{
    public function okAction(Request $req)
    {
        return "ok";
    }

    public function userListAction(Request $req)
    {
        /** @var User $user */
        $user = AuthController::authorize($req, $this);

        /** @var UserRepository $repo */
        $repo = $this->get('doctrine')->getRepository('OMAPIBundle:User');
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
        /** @var UserRepository $repo */
        $repo = $this->get('doctrine')->getRepository('OMAPIBundle:User');
        $repo->updateLastLogin($user);
        $profile = $repo->getProfile($id, array(
            'self' => $user,
            'fields' => array('id', 'username', 'email', 'lastLogin')
        ));
        if (!$profile) {
            throw new \Exception("Requested user inactive or not exists", Validation::USER_NOT_FOUND);
        }

        return $profile;
    }

    public function updateAction(Request $req)
    {
        $user = AuthController::authorize($req, $this);
        $isUpdate = false;
        if ($req->params->has('username')) {
            $username = $req->params->get('username');
            $isUpdate |= ($username != $user->getUsername());
            $user->setUsername($username);
        }
        if ($req->params->has('email')) {
            $email = $req->params->get('email');
            $isUpdate |= $email != $user->getEmail();
            $user->setUsername($email);
        }
        if ($req->params->has('password')) {
            $isUpdate = true;
            /** @var EncoderFactoryInterface $factory */
            $factory = $this->get('security.encoder_factory');
            $encoder = $factory->getEncoder($user);
            $user->setPassword($encoder->encodePassword($req->params->get('password'), $user->getSalt()));
        }
        if ($isUpdate) {
            $val = new Validation($this->get('validator'));
            $errors = $val->checkUniques($user, array('username', 'email'));
            if ($errors) {
                throw new \Exception($errors, Validation::UNIQUE_FAILED);
            }
            /** @var EntityManager $em */
            $em = $this->get('doctrine')->getManager();
            $em->persist($user);
            $em->flush();
            return "User updated successfully";
        }
        return "Nothing updated";
    }
}

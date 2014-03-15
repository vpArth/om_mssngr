<?php

namespace OM\APIBundle\Controller;

use Doctrine\ORM\EntityManager;
use OM\APIBundle\Entity\UserModelManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;
use OM\APIBundle\Entity\UserRepository;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserController extends Controller implements ISignedController
{
    public function userListAction(Request $req)
    {
        /** @var User $user */
        $user = AuthController::authorize($req, $this);

        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');
        $params = array();
        $params['type'] = $userMM::WIDGET_ALL;
        $params['fields'] = array('id', 'username', 'lastLogin');
        if ($req->params->has('page')) {
            $params['page'] = $req->params->get('page');
        }
        if ($req->params->has('size')) {
            $params['size'] = $req->params->get('size');
        }
        return $userMM->widget($params);
    }

    public function profileAction(Request $req, $id)
    {
        $user = AuthController::authorize($req, $this);
        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');
        return $userMM->getPublicProfile($id);
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

<?php

namespace OM\APIBundle\Controller;

use OM\APIBundle\Entity\MessageModelManager;
use OM\APIBundle\Entity\UserModelManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;
use OM\APIBundle\Entity\Message;

class MessageController extends Controller implements ISignedController
{
    public function okAction(Request $req)
    {
        if ($token = $req->params->get('token')) {
            $user = AuthController::authorize($req, $this);
            return "Hello, " . $user->getUsername();
        }
        return "okay";
    }
    public function listAction(Request $req)
    {
        AuthController::authorize($req, $this);

        /** @var MessageModelManager $msgMM */
        $msgMM = $this->get('omapi.message_model_manager');

        $params = array();
        $params['type'] = $msgMM::WIDGET_ALL;
        $params['fields'] = array('id', 'username', 'lastLogin');
        if ($req->params->has('page')) {
            $params['page'] = $req->params->get('page');
        }
        if ($req->params->has('size')) {
            $params['size'] = $req->params->get('size');
        }
        if ($req->params->has('textLength')) {
            $params['textLength'] = $req->params->get('textLength');
        }
        return $msgMM->widget($params);
    }

    public function dialogAction(Request $req, $with)
    {
        /** @var User $user */
        $user = AuthController::authorize($req, $this);

        /** @var MessageModelManager $msgMM */
        $msgMM = $this->get('omapi.message_model_manager');
        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');

        $withUser = $userMM->find($with);
        if (!$withUser) {
            throw new \Exception("Opponent not found", Validation::USER_NOT_FOUND);
        }

        if ($req->params->has('page')) {
            $params['page'] = $req->params->get('page');
        }
        if ($req->params->has('size')) {
            $params['size'] = $req->params->get('size');
        }
        $params['with'] = array($with, $user->getId());
        $params['type'] = $msgMM::WIDGET_DIALOG;
        return $msgMM->widget($params);
    }

    public function postAction(Request $req, $to)
    {
        $user = AuthController::authorize($req, $this);
        $val = new Validation($this->get('validator'));
        $val->valParams($req, array(
            'required' => array('text')
        ));
        /** @var MessageModelManager $msgMM */
        $msgMM = $this->get('omapi.message_model_manager');
        /** @var UserModelManager $userMM */
        $userMM = $this->get('omapi.user_model_manager');

        /** @var User $toUser */
        $toUser = $userMM->find($to);

        if (!$toUser) {
            throw new \Exception("Opponent not found", Validation::USER_NOT_FOUND);
        }

        $msg = new Message();
        $msg->setText($req->params->get('text'));
        $msg->setFromUser($user);
        $msg->setToUser($toUser);
        $msg->setCreated(time());

        $msgMM->save($msg);
        return "Message posted";
    }
}

<?php

namespace OM\APIBundle\Controller;

use OM\APIBundle\Entity\MessageModelManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use OM\APIBundle\Helper\Request;
use OM\APIBundle\Helper\Validation;
use OM\APIBundle\Entity\User;
use OM\APIBundle\Entity\Message;
use OM\APIBundle\Entity\UserRepository;
use OM\APIBundle\Entity\MessageRepository;
use Doctrine\ORM\EntityManager;

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
        /** @var User $user */
        $user = AuthController::authorize($req, $this);

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

        /** @var MessageRepository $mrepo */
        $mrepo = $this->get('doctrine')->getRepository('OMAPIBundle:Message');

        $params = array();
        $params['exclude_id'] = $user->getId();
        $params['fields'] = array('id', 'text', 'from', 'to');

        if ($req->params->has('page')) {
            $params['page'] = $req->params->get('page');
        }
        if ($req->params->has('size')) {
            $params['size'] = $req->params->get('size');
        }
        $params['with'] = array($with, $user->getId());
        $list = $mrepo->dialog($params);
        return $list;
    }

    public function postAction(Request $req, $to)
    {
        $user = AuthController::authorize($req, $this);
        $val = new Validation($this->get('validator'));
        $val->valParams($req, array(
            'required' => array('text')
        ));
        /** @var UserRepository $urepo */
        $urepo = $this->get('doctrine')->getRepository('OMAPIBundle:User');
        /** @var User $toUser */
        $toUser = $urepo->find($to);

        $msg = new Message();
        $msg->setText($req->params->get('text'));
        $msg->setFromUser($user);
        $msg->setToUser($toUser);
        $msg->setCreated(time());

        /** @var EntityManager $em */
        $em = $this->get('doctrine')->getManager();
        $em->persist($msg);
        $em->flush();

        return "Message posted";
    }
}

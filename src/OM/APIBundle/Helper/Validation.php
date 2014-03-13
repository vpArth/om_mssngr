<?php

namespace OM\APIBundle\Helper;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationInterface;

class Validation
{
    private $validator;

    public function __construct($validator)
    {
        if (!$validator instanceof Validator) {
            throw new \Exception('Symfony\Component\Validator\Validator expected, '.get_class($validator).' given');
        }
        $this->validator = $validator;
    }

    private function getParam(Request $req, $field, $default = null)
    {
        $content = $req->getContent();
        $params = empty($content) ? array() : json_decode($content, true);
        $param = isset($params[$field]) ? $params[$field] : $default;
        return $req->get($field, $param);
    }

    private function checkConstraint(
        Request $req,
        array $fields,
        Constraint $constraint,
        $msg = 'Field {{field}} is invalid'
    ) {
        foreach ($fields as $field) {
            $value = $this->getParam($req, $field);
            $constraint->message = str_replace("{{field}}", $field, $msg);
            /** @var $errorList ConstraintViolationList */
            $errorList = $this->validator->validateValue(
                $value,
                $constraint
            );
            if (count($errorList) != 0) {
                /** @var $error ConstraintViolationInterface*/
                $error = $errorList[0];
                return $error->getMessage();
            }
        }
        return 0;
    }

    public function checkRequired(Request $req, array $fields)
    {
        return $this->checkConstraint(
            $req,
            $fields,
            new NotBlank(),
            'Field {{field}} is required'
        );
    }

    public function checkEmails(Request $req, array $fields)
    {
        return $this->checkConstraint(
            $req,
            $fields,
            new Email(),
            'Field {{field}} isn\'t correct email'
        );
    }

    public function validate(Request $req, array $rules)
    {
        foreach ($rules as $rule => $fields) {
            switch ($rule) {
                case 'required':
                    if ($res = $this->checkRequired($req, $fields)) {
                        return $res;
                    }
                    break;
                case 'email':
                    if ($res = $this->checkEmails($req, $fields)) {
                        return $res;
                    }
                    break;
                default:
                    throw new \Exception("Unknown validation rule: $rule", 0xDEADBEEF);
            }
        }
        return 0;
    }
}

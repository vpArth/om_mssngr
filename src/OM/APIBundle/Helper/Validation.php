<?php

namespace OM\APIBundle\Helper;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Validator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
class Validation
{
    const INVALID_PARAMS = 22004;
    const UNIQUE_FAILED  = 22005;
    const NOT_AUTHORIZED = 21001;
    const BAD_TOKEN = 21002;
    const BAD_CREDENTIALS = 21003;
    const USER_NOT_FOUND = 21004;

    private $validator;

    public function __construct($validator)
    {
        if (!$validator instanceof Validator) {
            throw new \Exception('Symfony\Component\Validator\Validator expected, '.get_class($validator).' given');
        }
        $this->validator = $validator;
    }

    private function checkConstraint(
        Request $req,
        array $fields,
        Constraint $constraint,
        $msg = 'Field {{field}} is invalid'
    ) {
        foreach ($fields as $field) {
            $value = $req->params->get($field);
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

    public function checkUniques($entity, array $fieldsets)
    {
        foreach ($fieldsets as $fieldset) {
            $constraint = new UniqueEntity(array('fields'=>$fieldset));
            if (!is_array($fieldset)) {
                $fieldset = array($fieldset);
            }
            $constraint->message = "This ".implode(', ', $fieldset). " already used";
            /** @var $errorList ConstraintViolationList */
            $errorList = $this->validator->validateValue(
                $entity,
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

    public function valParams(Request $req, array $rules)
    {
        $errors = $this->validate($req, $rules);
        if ($errors) {
            throw new \Exception($errors, Validation::INVALID_PARAMS);
        }
    }
}

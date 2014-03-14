<?php

namespace OM\APIBundle\Helper;

use Symfony\Component\HttpFoundation\Request as R;
use Symfony\Component\HTTPFoundation\ParameterBag;

class Request extends R
{
    /** @var ParameterBag $params */
    public $params;

    public static function createFromGlobals()
    {
        /** @var Request $request */
        $request = parent::createFromGlobals();

        if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $request->request = new ParameterBag($data);
        }
        $params = array_merge(
            $request->request->all(),
            $request->query->all(),
            $request->attributes->all()
        );
        $request->params = new ParameterBag($params);
        return $request;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: mustafaileri
 * Date: 11/03/14
 * Time: 00:52
 */

namespace Hezarfen\DenetmenBundle\Validator;

use Guzzle\Http\Message\Response;

interface ValidatorInterface
{
    public function validate($routeKey, Response $response,  $config);
}

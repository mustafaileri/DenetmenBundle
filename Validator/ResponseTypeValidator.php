<?php
/**
 * Created by PhpStorm.
 * User: mustafaileri
 * Date: 11/03/14
 * Time: 00:19
 */

namespace Hezarfen\DenetmenBundle\Validator;

use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Exception\WrongContentTypeException;

class ResponseTypeValidator implements ValidatorInterface
{
    public function validate($routeKey, Response $response,  $expectedContentType)
    {
        if ($expectedContentType != $response->getContentType()) {
            throw new WrongContentTypeException("Wrong content type: " . $expectedContentType);
        }
    }
} 
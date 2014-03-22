<?php


namespace Hezarfen\DenetmenBundle\Validator;


use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Exception\MaxResponseTimeException;

class MaxResponseTimeValidator implements ValidatorInterface
{
    public function validate($routeKey, Response $response, $config)
    {
        if ($response->getInfo()["total_time"] > $config) {
            throw new MaxResponseTimeException(
                sprintf("Total time: %s, Expected maximum response time: %s",
                    number_format($response->getInfo()["total_time"], 2), number_format($config, 2)));
        }
    }


} 
<?php

namespace Hezarfen\DenetmenBundle\Validator;


use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Exception\ResponseFilterException;
use Symfony\Component\DomCrawler\Crawler;

class ResponseFilterValidator implements ValidatorInterface
{
    /** @var  Crawler */
    private $crawler;

    public function __construct($crawler)
    {
        $this->crawler = new $crawler;
    }
    public function validate($routeKey, Response $response, $filters)
    {
        $this->crawler->addHtmlContent($response->getBody(true));
        foreach ($filters as $key => $filterRules) {
            foreach ($filterRules as $filter) {
                $currentValue = $this->getCurrentValue($key, $filter[0]);
                if ($currentValue != $filter[1]) {
                    throw new ResponseFilterException(sprintf('Filter error: "%s"', $filter[0]));
                }
            }
        }
    }

    private function getCurrentValue($key, $selector)
    {
        if ($key == "node_traversing") {
            return $this->crawler->filter($selector)->text();
        }
    }

} 
<?php
/**
 * Class DenetmenService
 * @package Hezarfen\DenetmenBundle\Service
 */


namespace Hezarfen\DenetmenBundle\Service;


use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;
use Guzzle\Service\Client;
use Hezarfen\DenetmenBundle\Event\ErrorEvent;
use Hezarfen\DenetmenBundle\Exception\DenetmenCommonException;
use Hezarfen\DenetmenBundle\Exception\WrongContentTypeException;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Route;

class DenetmenService
{
    /** @var  Router */
    private $router;

    /** @var  array */
    private $config;

    /** @var  Client */
    private $guzzleClient;

    /** @var  \AppKernel */
    private $kernel;

    /** @var  EventDispatcher */
    private $eventDispatcher;

    /** @var  Crawler */
    private $crawler;

    public function __construct($config, $router, $guzzleClient, $eventDispatcher, $crawler)
    {
        $this->config = $config;
        $this->router = $router;
        $this->guzzleClient = new $guzzleClient;
        $this->eventDispatcher = $eventDispatcher;
        $this->crawler = $crawler;
    }

    /**
     * Get all routes
     * @return \Symfony\Component\Routing\Route[]
     */
    public function getAllRoutes()
    {
        return $this->router->getRouteCollection()->all();
    }

    /**
     * Get callable routes for current rules
     * @param array $routes
     * @param $regexPattern
     * @return array
     */
    public function getCallableRoutes(array $routes, $regexPattern)
    {

        /** @var Route $route */
        foreach ($routes as $routeKey => $route) {
            //Removed excluded routes.
            if (in_array($routeKey, $this->config["excluded"])) {
                unset($routes[$routeKey]);
                continue;
            }

            //Regex pattern applied if it defined.
            if ($regexPattern && !preg_match($regexPattern, $routeKey)) {
                unset($routes[$routeKey]);
                continue;
            }

            $sizeOfRouteMethods = sizeof($route->getMethods());
            if (!($sizeOfRouteMethods == 0 || in_array("GET", $route->getMethods()))) {
                unset($routes[$routeKey]);
                continue;
            }
        }
        return $routes;
    }

    /**
     * Call route urls
     * @param array $routes
     * @return array
     */
    public function callRoutesUrl(array $routes)
    {
        $rows = array();
        $errors = array();
        foreach ($routes as $routeKey => $route) {
            $responseRow = array();
            $responseRow['url'] = "";
            $responseRow['routeKey'] = $routeKey;
            $responseRow['statusCode'] = "";
            $responseRow['responseTime'] = "";
            $responseRow['exception'] = "";

            try {
                $url = $this->generateUrlForRoute($routeKey, $route);
                $this->guzzleClient->setBaseUrl($this->config['base_url']);
                $responseRow['url'] = $url;
                $response = $this->guzzleClient->createRequest("GET", $url)->send();
                $responseRow['statusCode'] = $response->getStatusCode();
                $responseRow['responseTime'] = $response->getInfo()["total_time"];

                if (isset ($this->config["router_configs"][$routeKey]["response"])) {
                    $this->checkResponseStatements($response, $routeKey, $responseRow);
                }

                $this->formatRow("info", $responseRow);
                $type = "info";
            } catch (BadResponseException $e) {
                $responseRow['statusCode'] = $e->getResponse()->getStatusCode();
                $type = "error";
                $responseRow['exception'] = get_class($e);
                array_push($errors, $responseRow);
            } catch (MissingMandatoryParametersException $e) {
                $type = "error";
                $responseRow['exception'] = "BadResponseException";
                array_push($errors, $responseRow);
            } catch (DenetmenCommonException $e) {
                $type = "error";
                $responseRow['exception'] = $e->getMessage();
                array_push($errors, $responseRow);
            }

            array_push($rows, $this->formatRow($type, $responseRow));
        }

        if (sizeof($errors) > 0) {
            $errorEvent = new ErrorEvent();
            $errorEvent->setErrorRows($errors);
            $this->eventDispatcher->dispatch('hezarfen.denetmen.events.error', $errorEvent);
        }

        return $rows;
    }

    /**
     * Generate url for route object
     * @param $routeKey
     * @param Route $route
     * @return string
     */
    public function generateUrlForRoute($routeKey, Route $route)
    {
        /** @var Route $route */
        preg_match_all("/[^{}]+(?=\})/", $route->getPath(), $match);
        $matched = preg_match_all("/[^{}]+(?=\})/", $route->getPath(), $match);
        $parameters = array();
        if ($matched > 0) {
            foreach ($match as $key) {
                $key = current($key);
                if (isset($this->config["router_configs"][$routeKey]["parameters"][$key])) {
                    $parameters[$key] = $this->config["router_configs"][$routeKey]["parameters"][$key];
                } elseif (isset($this->config["router_configs"]["general"][$key])) {
                    $parameters[$key] = $this->config["router_configs"]["general"][$key];
                } else {
                    continue;
                }
            }
        }
        return $this->router->generate($routeKey, $parameters);
    }

    /**
     * Formatting rows bye type
     * @param $type
     * @param array $row
     * @return array
     */
    public function formatRow($type, array $row)
    {
        return array_map(function ($item) use ($type) {
            return "<" . $type . ">" . $item . "</" . $type . ">";
        }, $row);
    }

    public function checkResponseStatements(Response $response, $routeKey, array $responseRow)
    {
        /** @var Crawler $crawler */
        $crawler = new $this->crawler();
        $crawler->addHtmlContent($response->getBody());
        var_dump($crawler->filter("body > header >  div > nav > ul > li")->text());
        if (isset ($this->config["router_configs"][$routeKey]["response"]["type"])
            && $this->config["router_configs"][$routeKey]["response"]["type"] != $response->getContentType()
        ) {
            throw new WrongContentTypeException("Wrong content type: " . $this->config["router_configs"][$routeKey]["response"]["type"]);
        }


    }
}

<?php
/**
 * Class DenetmenService
 * @package Hezarfen\DenetmenBundle\Service
 */

namespace Hezarfen\DenetmenBundle\Service;

use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Message\Response;
use Guzzle\Service\Client;
use Hezarfen\DenetmenBundle\Exception\DenetmenCommonException;
use Hezarfen\DenetmenBundle\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DenetmenService
{
    /** @var  Router */
    private $router;

    /** @var  array */
    private $config;

    /** @var  Client */
    private $guzzleClient;

    /** @var  array */
    private $validators;

    /** @var  RouteCollection */
    private $routeCollection;

    /**
     * Construction Method
     * @param $config
     * @param $router
     * @param $guzzleClient
     * @param $validators
     */
    public function __construct($config, $router, $guzzleClient, $validators)
    {
        $this->setConfig($config) ;
        $this->setRouter($router);
        $this->setGuzzleClient(new $guzzleClient);
        $this->setValidators($validators);
        $this->setRouteCollection($this->router->getRouteCollection());
    }

    /**
     * Config setter
     * @param $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * Config getter
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Guzzle client setter
     * @param \Guzzle\Service\Client $guzzleClient
     */
    public function setGuzzleClient($guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * Guzzle client getter
     * @return \Guzzle\Service\Client
     */
    public function getGuzzleClient()
    {
        return $this->guzzleClient;
    }

    /**
     * Set RouteCollection
     * @param \Symfony\Component\Routing\RouteCollection $routeCollection
     */
    public function setRouteCollection($routeCollection)
    {
        $this->routeCollection = $routeCollection;
    }

    /**
     * Get RouteCollection
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection()
    {
        return $this->routeCollection;
    }

    /**
     * Set Router
     * @param \Symfony\Bundle\FrameworkBundle\Routing\Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * Get Router
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Set Validators
     * @param array $validators
     */
    public function setValidators($validators)
    {
        $this->validators = $validators;
    }

    /**
     * Get Validators
     * @return array
     */
    public function getValidators()
    {
        return $this->validators;
    }

    /**
     * Get all routes
     * @return \Symfony\Component\Routing\Route[]
     */
    public function getAllRoutes()
    {
        return $this->routeCollection->all();
    }

    /**
     * Get callable routes for current rules
     * @param  array $routes
     * @param $regexPattern
     * @return array
     */
    public function getCallableRoutes(array $routes, $regexPattern)
    {
        /** @var Route $route */
        foreach ($routes as $routeKey => $route) {
            //Removed excluded routes.
            if (isset($this->config["excluded"]) && in_array($routeKey, $this->config["excluded"])) {
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
     * @param  Route $route
     * @param $routeKey
     * @return array
     */
    public function callRouteUrl(Route $route, $routeKey)
    {
            $responseRow = array();
            $responseRow['url'] = "";
            $responseRow['routeKey'] = $routeKey;
            $responseRow['statusCode'] = "";
            $responseRow['responseTime'] = "";
            $responseRow['exception'] = "";
            try {
                $responseRow = $this->trySendRequest($routeKey, $route, $responseRow);
            } catch (BadResponseException $e) {
                $responseRow["url"] = $this->generateUrlForRoute($routeKey, $route);
                $responseRow['statusCode'] = $e->getResponse()->getStatusCode();
                $reflector = new \ReflectionClass($e);
                $responseRow['exception'] = $reflector->getShortName();
            } catch (MissingMandatoryParametersException $e) {
                $responseRow['exception'] = "BadResponseException";
            } catch (DenetmenCommonException $e) {
                $responseRow["url"] = $this->generateUrlForRoute($routeKey, $route);
                $responseRow['exception'] = $e->getMessage();
            }

        return $responseRow;
    }

    /**
     * Generate url for route object
     * @param $routeKey
     * @param  Route  $route
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
     * @param  array $row
     * @return array
     */
    public function formatRow($type, array $row)
    {
        return array_map(function ($item) use ($type) {
            return "<" . $type . ">" . $item . "</" . $type . ">";
        }, $row);
    }

    public function trySendRequest($routeKey, Route $route, array $responseRow)
    {
        $url = $this->generateUrlForRoute($routeKey, $route);
        $this->guzzleClient->setBaseUrl($this->config['base_url']);
        $responseRow['url'] = $url;
        $response = $this->guzzleClient->createRequest("GET", $url)->send();
        $responseRow['statusCode'] = $response->getStatusCode();
        $responseRow['responseTime'] = $response->getInfo()["total_time"];

        if (isset ($this->config["router_configs"][$routeKey]["response"])) {
            $this->checkResponseStatements($response, $routeKey, $responseRow);
        }

        return $responseRow;
    }

    public function checkResponseStatements(Response $response, $routeKey, array $responseRow)
    {
        foreach ($this->config["router_configs"][$routeKey]["response"] as $key => $value) {
            /** @var ValidatorInterface $validator */
            $validator = $this->validators[$key];
            $validator->validate($routeKey, $response, $value);
        }
    }
}

<?php
/**
 * Class DenetmenService
 * @package Hezarfen\DenetmenBundle\Service
 */


namespace Hezarfen\DenetmenBundle\Service;


use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Service\Client;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Route;

class DenetmenService
{
    /** @var  Router */
    private $router;

    /** @var  array */
    protected $config;

    /** @var  Client */
    protected $guzzleClient;

    /** @var  \AppKernel */
    protected $kernel;

    public function __construct($config, $router, $guzzleClient, $kernel)
    {
        $this->config = $config;
        $this->router = $router;
        $this->guzzleClient = new $guzzleClient;
        $this->kernel = $kernel;
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
        foreach ($routes as $routeKey => $route) {
            try {
                $url = $this->generateUrlForRoute($routeKey, $route);
                $this->guzzleClient->setBaseUrl($this->config['base_url']);
                $responseRow = array();
                $responseRow['url'] = $url;
                $responseRow['routeKey'] = $routeKey;

                $request = $this->guzzleClient->createRequest("GET", $url)->send();
                $responseRow['statusCode'] = $request->getStatusCode();
                $responseRow['responseTime'] = $request->getInfo()["total_time"];
                $this->formatRow("info", $responseRow);
                $type = "info";
            } catch (BadResponseException $e) {
                $responseRow['statusCode'] = $e->getResponse()->getStatusCode();
                $type = "error";
            } catch (MissingMandatoryParametersException $e) {
                $responseRow['url'] = $url;
                $responseRow['routeKey'] = $routeKey;
                $responseRow['statusCode'] = "";
                $type = "error";
            }

            array_push($rows, $this->formatRow($type, $responseRow));
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
                if (isset($this->config["routerConfigs"][$routeKey][$key])) {
                    $parameters[$key] = $this->config["parameters"][$routeKey][$key];
                } elseif (isset($this->config["parameters"]["general"][$key])) {
                    $parameters[$key] = $this->config["parameters"]["general"][$key];
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
        return array_map(function($item) use ($type) {
            return "<" . $type . ">" . $item . "</" . $type . ">";
        }, $row);
    }
}
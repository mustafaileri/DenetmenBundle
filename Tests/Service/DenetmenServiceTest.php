<?php


namespace Hezarfen\DenetmenBundle\Tests\Service;


use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Service\DenetmenService;
use Hezarfen\DenetmenBundle\Validator\MaxResponseTimeValidator;
use Hezarfen\DenetmenBundle\Validator\ResponseFilterValidator;
use Hezarfen\DenetmenBundle\Validator\ResponseTypeValidator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Yaml;

class DenetmenServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DenetmenService */
    private $service;

    /** @var  array */
    private $configMock;

    private $routerMock;

    private $validatorsMock;

    private $routeCollection;

    private $guzzleClientMock;

    private $htmlMockContent = array();

    protected function setUp()
    {
        $this->setMockObjects();
        $service = new DenetmenService($this->configMock, $this->routerMock, 'Guzzle\Service\Client', $this->validatorsMock);
        $service->setRouteCollection($this->routeCollection);
        $service->setGuzzleClient($this->guzzleClientMock);
        $this->service = $service;
    }

    private function setMockObjects()
    {
        $this->setRouteCollectionMock();
        $this->setConfigMock();
        $this->setRouterMock();
        $this->setValidatorsMock();
        $this->loadMockHtmlContent();
        $this->setGuzzleClientMock();

    }

    private function setConfigMock()
    {
        $this->configMock = Yaml::parse(file_get_contents(__DIR__ . " /../Resources/valid_configuration.yml"))['parameters']['denetmen'];
    }

    private function setRouterMock()
    {
        $routerMock = $this->getMockBuilder("Symfony\Bundle\FrameworkBundle\Routing\Router")->disableOriginalConstructor()
            ->disableOriginalConstructor()->setMethods(array("all", "getRouteCollection", "generate"))->getMock();

        $routerMock->expects($this->any())->method("all")->will($this->returnValue(array()));

        $routerMock->expects($this->any())->method("getRouteCollection")->will($this->returnValue($this->routeCollection));
        $routerMock->expects($this->any())->method("generate")->withAnyParameters()->will($this->returnCallback(array($this, "generateUrlCallback")));
        $this->routerMock = $routerMock;
    }

    public function generateUrlCallback($routeName)
    {
        return $this->routeCollection->get($routeName)->getPath();
    }

    private function setRouteCollectionMock()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add('denetmen_foo', new Route('/foo', array('controller' => 'NotExistController')), $this->routerMock);
        $routeCollection->add('denetmen_bar', new Route('/bar/{bar}', array('controller' => 'NotExistController')));
        $routeCollection->add('denetmen_post', new Route('/post', array('controller' => 'NotExistController', 'method' => "POST"), array(), array(), '', array(), "POST"), $this->routerMock);
        $routeCollection->add('denetmen_get', new Route('/get', array('controller' => 'NotExistController', 'method' => "GET"), array(), array(), '', array(), "GET"), $this->routerMock);
        $routeCollection->add('test', new Route('/test', array('controller' => 'NotExistController')), $this->routerMock);
        $routeCollection->add('excluded', new Route('/excluded', array('controller' => 'NotExistController')), $this->routerMock);

        $this->routeCollection = $routeCollection;
    }


    private function setValidatorsMock()
    {
        $this->validatorsMock = array(
            'type' => new ResponseTypeValidator(),
            'filter' => new ResponseFilterValidator(new Crawler()),
            'max_response_time' => new MaxResponseTimeValidator()
        );
    }

    private function setGuzzleClientMock()
    {
        $guzzleClientMock = $this->getMockBuilder('Guzzle\Service\Client')->disableOriginalConstructor()
            ->setMethods(array("createRequest"))->getMock();
        $guzzleClientMock->expects($this->any())->method("createRequest")->will($this->returnCallback(array($this, "guzzleCreateRequestCallback")));
        $this->guzzleClientMock = $guzzleClientMock;
    }

    private function loadMockHtmlContent()
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__. '/../Resources/templates/');

        foreach ($finder as $file) {
            /** @var $file \Symfony\Component\Finder\SplFileInfo */
            if ($file->isFile()) {
                $key = explode(".", $file->getFilename())[0];
                $this->htmlMockContent[$key] = $file->getContents();
            }
        }
    }

    public function guzzleCreateRequestCallback($method, $path)
    {
        $key = explode('/', $path)[1];
        $content = $this->htmlMockContent[$key];

        $responseObject = new Response(200, array(), $content, array());
        $responseObject->setHeader("Content-Type", "text/html; charset=utf-8");
        $responseObject->setInfo(array('total_time' => 1));

        $responseMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()->setMethods(array('send'))->getMock();

        $responseMock->expects($this->any())->method('send')->will($this->returnValue($responseObject));
        return $responseMock;

    }

    public function testGetConfig()
    {
        $this->assertTrue(is_array($this->service->getConfig()));
        $this->assertArrayHasKey("base_url", $this->service->getConfig());
    }

    public function testGetGuzzleClient()
    {
        $this->assertTrue($this->service->getGuzzleClient() instanceof \PHPUnit_Framework_MockObject_MockObject);
    }

    public function testGetRouteCollection()
    {
        $this->assertTrue($this->service->getRouteCollection() instanceof RouteCollection);
    }

    public function testGetValidators()
    {
        $this->assertTrue(is_array($this->service->getValidators()));
    }

    public function testGetAllRoutes()
    {
        $routes = $this->service->getAllRoutes();
        $this->assertTrue(is_array($routes));
        $this->assertTrue(sizeof($routes) == 6);
        $this->assertArrayHasKey('excluded', $routes);
    }

    public function testGetCallableRoutes()
    {
        $callableRoutes = $this->service->getCallableRoutes($this->service->getAllRoutes(), false);
        $this->assertTrue(is_array($callableRoutes));
    }

    public function testCallRouteUrl()
    {
        $callableRoutes =  $this->service->getCallableRoutes($this->service->getAllRoutes(), false);

        $response = $this->service->callRouteUrl($callableRoutes['denetmen_foo'], 'denetmen_foo');
        $this->assertTrue(!$response['exception']);
        $this->assertTrue($response['statusCode'] == 200);
        $this->assertTrue($response["responseTime"] == 1);

        $response = $this->service->callRouteUrl($callableRoutes['denetmen_bar'], 'denetmen_bar');
        $this->assertTrue(!$response['exception']);
        $this->assertTrue($response['statusCode'] == 200);
        $this->assertTrue($response["responseTime"] == 1);

        $response = $this->service->callRouteUrl($callableRoutes['denetmen_get'], 'denetmen_get');
        $this->assertTrue(!$response['exception']);
        $this->assertTrue($response['statusCode'] == 200);
        $this->assertTrue($response["responseTime"] == 1);

        $response = $this->service->callRouteUrl($callableRoutes['test'], 'test');
        $this->assertTrue(!$response['exception']);
        $this->assertTrue($response['statusCode'] == 200);
        $this->assertTrue($response["responseTime"] == 1);
    }

    public function testFormatRow()
    {
        $data = array('a', 'b');
        $data = $this->service->formatRow("info", $data);
        $this->assertTrue($data[0] == "<info>a</info>");
        $this->assertTrue($data[1] == "<info>b</info>");
    }

    public function testTrySendRequest()
    {
        $responseRow = $this->service->trySendRequest('denetmen_foo', $this->routeCollection->get('denetmen_foo'), array());
        $this->assertTrue(is_array($responseRow));
        $this->assertArrayHasKey('url', $responseRow);
        $this->assertArrayHasKey('statusCode', $responseRow);
        $this->assertArrayHasKey('responseTime', $responseRow);
    }
}
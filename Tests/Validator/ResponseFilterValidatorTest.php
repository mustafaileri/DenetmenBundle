<?php


namespace Hezarfen\DenetmenBundle\Tests\Validator;


use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Validator\ResponseFilterValidator;

class ResponseFilterValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Response */
    private $response;

    /** @var  ResponseFilterValidator */
    private $validator;

    protected function setUp()
    {
        $content = '<!DOCTYPE html><html><head><title></title></head><body>Test</body></html>';
        $this->response = new Response(200, array(), $content, array());
        $this->validator = new ResponseFilterValidator('\Symfony\Component\DomCrawler\Crawler');
    }

    public function testValidateSuccess()
    {
        $filters = array('node_traversing' => array(array('body', 'Test')));
        $this->validator->validate('test', $this->response, $filters);
    }

    public function testValidateFail()
    {
        $this->setExpectedException('Hezarfen\DenetmenBundle\Exception\ResponseFilterException');
        $filters = array('node_traversing' => array(array('body', 'Foo')));
        $this->validator->validate('test', $this->response, $filters);
    }
}
<?php


namespace Hezarfen\DenetmenBundle\Tests\Validator;


use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Validator\MaxResponseTimeValidator;

class MaxResponseTimeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Response */
    private $response;

    /** @var  MaxResponseTimeValidator */
    private $validator;

    protected function setUp()
    {
        $this->response = new Response(200);
        $this->validator = new MaxResponseTimeValidator();
    }

    public function testValidateSuccess()
    {
        $this->response->setInfo(array('total_time' => 1));
        $this->assertTrue(is_null($this->validator->validate('test', $this->response, 1)));
    }

    public function testValidateFail()
    {
        $this->setExpectedException("Hezarfen\DenetmenBundle\Exception\MaxResponseTimeException");
        $this->response->setInfo(array('total_time' => 2));
        $this->validator->validate('test', $this->response, 1);
    }
}
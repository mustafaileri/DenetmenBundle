<?php

namespace Hezarfen\DenetmenBundle\Tests\Validator;

use Guzzle\Http\Message\Response;
use Hezarfen\DenetmenBundle\Validator\ResponseTypeValidator;

class ResponseTypeValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var  Response */
    private $response;

    /** @var  ResponseTypeValidator */
    private $validator;

    protected function setUp()
    {
        $this->response = new Response(200);
        $this->response->setHeader('Content-type', 'text/html');
        $this->validator = new ResponseTypeValidator();
    }

    public function testValidateSuccess()
    {
        $this->validator->validate('test', $this->response, 'text/html');
    }

    public function testValidateFail()
    {
        $this->setExpectedException('Hezarfen\DenetmenBundle\Exception\WrongContentTypeException');
        $this->validator->validate('test', $this->response, 'application/force-download');
    }
}

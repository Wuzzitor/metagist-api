<?php
namespace Metagist\Api\Validation\Plugin;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the schema validator plugin for guzzle.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class SchemaValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\Guzzle\Plugin\SchemaValidator
     */
    private $validator;
    
    /**
     * guzzle client
     * 
     * @var \Guzzle\Http\Client 
     */
    private $client;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        
        $config = array(
            'basepath' => __DIR__,
            'mapping'  => array(
                'pushInfo' => realpath(__DIR__ . '/../../../../../../services/pushInfo.schema.json')
            )
        );
        $resolver = new \Metagist\Api\Validation\SchemaResolver($config);
        
        $this->validator = new SchemaValidator($resolver);
        $this->client    = new \Guzzle\Http\Client();
        $this->client->addSubscriber($this->validator);
    }
    
    /**
     * Ensures the plugin provides a callback to the 'request.sent' event.
     */
    public function testSubscribesToRequestSent()
    {
        $subscribed = SchemaValidator::getSubscribedEvents();
        $this->assertArrayHasKey('command.after_send', $subscribed);
        
        $callback = $subscribed['command.after_send'];
        $this->assertEquals('onCommandSent', $callback[0]);
    }
    
    /**
     * Simulates a successful validation.
     */
    public function testOnCommandSent()
    {
        $commandMock = $this->getMockBuilder("\Guzzle\Service\Command\OperationCommand")
            ->disableOriginalConstructor()
            ->getMock();
        $operation = $this->getMockBuilder("\Guzzle\Service\Description\Operation")
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pushInfo'));
        $commandMock->expects($this->once())
            ->method('getOperation')
            ->will($this->returnValue($operation));
        
        $body = '{"info":{"group":"testInteger","version":"0.1.1","value":1}}';
        $response = new \Guzzle\Http\Message\Response(200, null, $body);
        $commandMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));
        $event = new \Guzzle\Common\Event();
        $event['command'] = $commandMock;
        
        $this->setExpectedException(NULL);
        $this->validator->onCommandSent($event);
    }
    
    /**
     * Ensures an invalid response body leads to an exception.
     */
    public function testOnCommandSentHasNoSchemaReturnsNull()
    {
        $plugin   = new \Guzzle\Plugin\Mock\MockPlugin();
        $response = new \Guzzle\Http\Message\Response(200);
        $response->setBody(
            json_encode((object)array('group' => 'agroup'))
        );
        $plugin->addResponse($response);
        $this->client->addSubscriber($plugin);
        
        $request  = $this->client->get('http://test.com');
        $request->send();
    }
    
    /**
     * Ensures an invalid response body leads to an exception.
     */
    public function testOnCommandSentFails()
    {
        $commandMock = $this->getMockBuilder("\Guzzle\Service\Command\OperationCommand")
            ->disableOriginalConstructor()
            ->getMock();
        $operation = $this->getMockBuilder("\Guzzle\Service\Description\Operation")
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pushInfo'));
        $commandMock->expects($this->once())
            ->method('getOperation')
            ->will($this->returnValue($operation));
        
        $body = '{"info":{"group":"testInteger"}';
        $response = new \Guzzle\Http\Message\Response(200, null, $body);
        $commandMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($response));
        $event = new \Guzzle\Common\Event();
        $event['command'] = $commandMock;
        
        $this->setExpectedException("\Metagist\Api\Validation\Exception");
        $this->validator->onCommandSent($event);
    }
    
    /**
     * Ensures a valid message does not cause an exception to be thrown.
     */
    public function testValidateRequest()
    {
        $messageProvider = new \Metagist\Api\Test\MessageProvider();
        $message = $messageProvider->getPushInfoMessage();
        
        $factory = new \Guzzle\Http\Message\RequestFactory();
        $request = $factory->fromMessage($message);
        
        $this->setExpectedException(NULL);
        $this->validator->validateRequest($request, 'pushInfo');
    }
    
    /**
     * Ensures the resolver exception is not caught.
     */
    public function testValidateMessageHasNoSchema()
    {
        $messageProvider = new \Metagist\Api\Test\MessageProvider();
        $message = $messageProvider->getPushInfoMessage();
        
        $factory = new \Guzzle\Http\Message\RequestFactory();
        $request = $factory->fromMessage($message);
        
        $this->setExpectedException("\Metagist\Api\Validation\Exception");
        $this->validator->validateRequest($request, 'unknown');
    }

    /**
     * Ensures an invalid message causes an exception to be thrown.
     */
    public function testValidateMessageFails()
    {
        $factory = new \Guzzle\Http\Message\RequestFactory();
        $request = $factory->fromParts('POST', array(), null, '{"info":{"group":"testInteger"}');
        
        $this->setExpectedException("\Metagist\Api\Validation\Exception");
        $this->validator->validateRequest($request, 'pushInfo');
        
    }
}
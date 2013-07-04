<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the authentication listener
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class AuthenticationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\AuthenticationListener
     */
    private $listener;
    
    /**
     * request validator
     * 
     * @var \Metagist\Api\OAuthValidator
     */
    private $validator;
    
    /**
     * securityContext
     * 
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $securityContext;
    
    /**
     * manager
     * 
     * @var \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface
     */
    private $manager;
    
    /**
     * event dispatcher mock
     * 
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    private $dispatcher;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->validator       = $this->getMockBuilder("\Metagist\Api\OAuthValidator")
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext = $this->getMockBuilder("\Symfony\Component\Security\Core\SecurityContext")
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager         = $this->getMock("\Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface");
        
        $this->dispatcher = $this->getMock("\Symfony\Component\EventDispatcher\EventDispatcherInterface");
        
        $logger = $this->getMock("\Psr\Log\LoggerInterface");
        
        $this->listener = new \Metagist\Api\AuthenticationListener($this->securityContext, $this->manager, $this->validator, $logger);
    }
    
    /**
     * Ensures that a successful authentication trigger a success event.
     */
    public function testOnIncomingRequestIssuesSuccessEvent()
    {
        $this->validator->expects($this->once())
            ->method('validateRequest')
            ->will($this->returnValue('testconsumer'));
        
        $request = $this->getMockBuilder("\Guzzle\Http\Message\Request")
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->createEvent($request);
        
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                AuthenticationListener::EVENT_AUTHENTICATED,
                $this->isInstanceOf("\Symfony\Component\EventDispatcher\GenericEvent")
            );
        
        $this->listener->onIncomingRequest($event);
    }
    
    /**
     * Ensures that the validator exception is not caught.
     */
    public function testOnIncomingRequestThrowsException()
    {
        $this->validator->expects($this->once())
            ->method('validateRequest')
            ->will($this->throwException(new Exception('test')));
        
        $request = $this->getMockBuilder("\Guzzle\Http\Message\Request")
            ->disableOriginalConstructor()
            ->getMock();
        $event = $this->createEvent($request);
        
        
        $this->setExpectedException("\Metagist\Api\Exception", 'test');
        $this->listener->onIncomingRequest($event);
    }
    
    /**
     * Tests the reaction on authentication success.
     */
    public function testOnAuthenticationSuccess()
    {
        $this->manager->expects($this->once())
            ->method('authenticate');
        $this->securityContext->expects($this->once())
            ->method('setToken');
        
        $event = $this->createEvent('testconsumer');
        $this->listener->onAuthenticationSuccess($event);
    }
    
    /**
     * Creates an event with a mocked dispatcher.
     * 
     * @param mixed $subject
     * @return GenericEvent
     */
    protected function createEvent($subject)
    {
        $event = new \Symfony\Component\EventDispatcher\GenericEvent($subject);
        $event->setDispatcher($this->dispatcher);
        return $event;
    }
}
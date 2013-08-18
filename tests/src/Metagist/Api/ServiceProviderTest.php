<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the api service provider
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * @var Application
     */
    private $serviceProvider;
    
    /**
     * app
     * @var \Silex\Application
     */
    private $app;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->serviceProvider = new \Metagist\Api\ServiceProvider();
        $this->app = new \Silex\Application();
        $this->app[ServiceProvider::APP_CONSUMERS] = array(
            'worker1' => 'test'
        );
        $dispatcher = $this->getMock("\Symfony\Component\EventDispatcher\EventDispatcherInterface");
        $this->app['dispatcher'] = $dispatcher;
    }
    
    /**
     * Ensures the server provides registers itself.
     */
    public function testRegistersApiCallback()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        
        $this->serviceProvider->register($this->app);
        $api = $this->app[ServiceProvider::API];
        $this->assertEquals($this->serviceProvider, $api);
    }
    
    
    /**
     * Ensures the whole oath integration works. 
     */
    public function testOAuthIntegration()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_CONSUMERS] = array(
            'worker1' => 'test'
        );
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        
        
        $this->serviceProvider->register($this->app);
        
        $messageProvider = new \Metagist\Api\Test\MessageProvider();
        $message         = $messageProvider->getMessage('worker1', 'test');
        $request = $this->serviceProvider->getIncomingRequest($message);
        
        $this->setExpectedException(null);
        $this->serviceProvider->validateRequest($request);
    }
    
    /**
     * Ensures that the validateRequest() method triggers an event.
     */
    public function testValidateRequest()
    {
        $this->app[ServiceProvider::APP_CONSUMERS] = array('test' => 'test');
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        
        $request = $this->getMock("\Guzzle\Http\Message\RequestInterface");
        $dispatcher = $this->getMock("\Symfony\Component\EventDispatcher\EventDispatcherInterface");
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(AuthenticationListener::EVENT_INCOMING_REQUEST, $this->isInstanceOf("\Symfony\Component\EventDispatcher\GenericEvent"));
        
        $this->app['dispatcher'] = $dispatcher;
        $this->serviceProvider->register($this->app);
        
        $this->setExpectedException(null);
        $this->serviceProvider->validateRequest($request);
    }
    
    /**
     * Ensures that an exception is thrown if no consumers are configured.
     */
    public function testValidateRequestEnforcesConfiguration()
    {
        unset($this->app[ServiceProvider::APP_CONSUMERS]);
        $request = $this->getMock("\Guzzle\Http\Message\RequestInterface");
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->serviceProvider->validateRequest($request);
    }
    
    /**
     * Ensures an exception is thrown if the dispatcher is not present.
     */
    public function testValidateRequestNoDispatcherException()
    {
        unset($this->app['dispatcher']);
        $request = $this->getMock("\Guzzle\Http\Message\RequestInterface");
        $this->setExpectedException("\Metagist\Api\Exception", "Dispatcher instance not available");
        $this->serviceProvider->validateRequest($request);
    }
    
    /**
     * Ensures that the /api request path is protected using the api firewall.
     */
    public function testRegisteredAuthListenerFactory()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app['security.firewalls'] = array();
        $this->serviceProvider->register($this->app);
        $this->assertArrayHasKey('api', $this->app['security.firewalls']);
    }
}
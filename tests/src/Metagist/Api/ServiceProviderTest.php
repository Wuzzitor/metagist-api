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
    }
    
    /**
     * Ensures the server provides registers itself.
     */
    public function testRegistersApiCallback()
    {
        $this->serviceProvider->register($this->app);
        $api = $this->app[ServiceProvider::API];
        $this->assertEquals($this->serviceProvider, $api);
    }
    
    /**
     * Ensures the server() method returns an ServerInterface implementation.
     */
    public function testApiProvidesServerInterface()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_SERVER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Server.json'),
            'consumer_key' => 'metagist.org',
            'consumer_secret' => 'obey'
        );
        $this->serviceProvider->register($this->app);
        
        $server = $this->serviceProvider->server();
        $this->assertInstanceOf("\Metagist\Api\ServerInterface", $server);
        $this->assertInstanceOf("\Metagist\Api\ServerClient", $server);
    }
    
    /**
     * Ensures an exception is thrown if the server client is requested but not
     * configured.
     */
    public function testApiNoServerInterfaceException()
    {
        $this->serviceProvider->register($this->app);
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->serviceProvider->server();
    }
    
    /**
     * Ensures the worker() method returns an WorkerInterface implementation.
     */
    public function testApiProvidesWorkerInterface()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->serviceProvider->register($this->app);
        
        $worker = $this->serviceProvider->worker();
        $this->assertInstanceOf("\Metagist\Api\WorkerInterface", $worker);
        $this->assertInstanceOf("\Metagist\Api\WorkerClient", $worker);
    }
    
    /**
     * Ensures an exception is thrown if the worker client is requested but not
     * configured.
     */
    public function testApiNoWorkerInterfaceException()
    {
        $this->serviceProvider->register($this->app);
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->serviceProvider->worker();
    }
    
    /**
     * Ensures an exception is thrown if the oauth configuration is missing.
     */
    public function testApiNoOauthConfigurationException()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Server.json'),
        );
        $this->serviceProvider->register($this->app);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'OAuth');
        $this->serviceProvider->worker();
    }
    
    /**
     * Ensures a log plugin is listening if a logger is available.
     */
    public function testServiceProviderAddsMonologPlugin()
    {
        $this->app['monolog'] = new \Monolog\Logger('test');
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->serviceProvider->register($this->app);
        
        $worker = $this->serviceProvider->worker();
        /* @var $worker \Guzzle\Service\Client */
        $listeners = $worker->getEventDispatcher()->getListeners('request.before_send');
        foreach ($listeners as $data) {
            if ($data[0] instanceof \Guzzle\Plugin\Log\LogPlugin) {
                return;
            }
        }
        
        $this->fail('No log plugin found.');
    }
    
    /**
     * Ensures that an oauth plugin is listening.
     */
    public function testServiceProviderAddsOauthPlugin()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->serviceProvider->register($this->app);
        
        $worker = $this->serviceProvider->worker();
        /* @var $worker \Guzzle\Service\Client */
        $listeners = $worker->getEventDispatcher()->getListeners('request.before_send');
        foreach ($listeners as $data) {
            if ($data[0] instanceof \Guzzle\Plugin\Oauth\OauthPlugin) {
                return;
            }
        }
        
        $this->fail('No oauth plugin found.');
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
        
        $this->setExpectedException(null);
        $this->serviceProvider->validateRequest($message);
    }
    
}
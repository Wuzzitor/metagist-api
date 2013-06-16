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
     * Ensures an exception is thrown if the oauth configuration is missing.
     */
    public function testApiNoDescriptionConfigurationException()
    {
        $this->app[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
        );
        $this->serviceProvider->register($this->app);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'description');
        $this->serviceProvider->worker();
    }
}
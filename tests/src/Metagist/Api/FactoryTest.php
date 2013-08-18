<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the api factroy
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * @var Factory
     */
    private $factory;
    
    /**
     * factory config
     * 
     * @var array
     */
    private $config;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->factory = new \Metagist\Api\Factory();
    }
    
    /**
     * Ensures the server() method returns an ServerInterface implementation.
     */
    public function testApiProvidesServerInterface()
    {
        $this->config[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->config[ServiceProvider::APP_SERVER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Server.json'),
            'consumer_key' => 'metagist.org',
            'consumer_secret' => 'obey'
        );
        $this->factory->setConfig($this->config);
        
        $server = $this->factory->getServerClient();
        $this->assertInstanceOf("\Metagist\Api\ServerInterface", $server);
        $this->assertInstanceOf("\Metagist\Api\ServerClient", $server);
    }
    
    /**
     * Ensures an exception is thrown if the server client is requested but not
     * configured.
     */
    public function testApiNoServerInterfaceException()
    {
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->factory->getServerClient();
    }
    
    /**
     * Ensures the worker() method returns an WorkerInterface implementation.
     */
    public function testApiProvidesWorkerInterface()
    {
        $this->config[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->config[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->factory->setConfig($this->config);
        
        $worker = $this->factory->getWorkerClient();
        $this->assertInstanceOf("\Metagist\Api\WorkerInterface", $worker);
        $this->assertInstanceOf("\Metagist\Api\WorkerClient", $worker);
    }
    
    /**
     * Ensures an exception is thrown if the worker client is requested but not
     * configured.
     */
    public function testApiNoWorkerInterfaceException()
    {
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->factory->getWorkerClient();
    }
    
    /**
     * Ensures an exception is thrown if the oauth configuration is missing.
     */
    public function testApiNoOauthConfigurationException()
    {
        $this->config[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->config[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Server.json'),
        );
        $this->factory->setConfig($this->config);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'OAuth');
        $this->factory->getWorkerClient();
    }
    
    /**
     * Ensures a log plugin is listening if a logger is available.
     */
    public function testServiceProviderAddsMonologPlugin()
    {
        $this->config['monolog'] = new \Monolog\Logger('test');
        $this->config[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->config[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->factory->setConfig($this->config);
        
        $worker = $this->factory->getWorkerClient();
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
        $this->config[ServiceProvider::APP_SERVICES] = __DIR__ . '/testdata/testservices.json';
        $this->config[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://test.com',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
            'consumer_key' => 'worker1',
            'consumer_secret' => 'test'
        );
        $this->factory->setConfig($this->config);
        
        $worker = $this->factory->getWorkerClient();
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
     * Ensures a JMS serializer is returned
     */
    public function testGetSerializer()
    {
        $serializer = $this->factory->getSerializer();
        $this->assertInstanceOf("\JMS\Serializer\SerializerInterface", $serializer);
    }
    
    /**
     * Ensures the service provider returns a schema validator
     */
    public function testGetSchemaValidator()
    {
        $validator = $this->factory->getSchemaValidator();
        $this->assertInstanceOf("\Metagist\Api\Validation\Plugin\SchemaValidator", $validator);
    }
    
    /**
     * Ensures the incoming request is returned as object
     */
    public function testGetIncomingRequest()
    {
        $_POST['test'] = 'test';
        $_SERVER['HTTPS'] = 'on';
        $request = $this->factory->getIncomingRequest();
        $this->assertInstanceOf("\Guzzle\Http\Message\Request", $request);
    }
    
    public function testGetRequestValidator()
    {
        $this->factory->setConfig(
            array('dispatcher' => $dispatcher = $this->getMock("\Symfony\Component\EventDispatcher\EventDispatcherInterface"))
        );
        $validator = $this->factory->getRequestValidator();
        $this->assertInstanceOf("\Metagist\Api\RequestValidator", $validator);
    }
    
    public function testGetRequestValidatorException()
    {
        $this->setExpectedException("\Metagist\Api\Exception");
        $this->factory->getRequestValidator();
    }
}
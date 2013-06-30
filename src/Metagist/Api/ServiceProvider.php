<?php
namespace Metagist\Api;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Description\ServiceDescription;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;

/**
 * Silex service provider which registers metagist api clients.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @link https://github.com/guzzle/guzzle-silex-extension/blob/master/GuzzleServiceProvider.php
 */
class ServiceProvider implements ServiceProviderInterface, ApiProviderInterface
{
    /**
     * App key under which the service provider is registered.
     * 
     * @var string
     */
    const API = 'metagist.api';
    
    /**
     * App key under which the services config is registered.
     * 
     * @var string
     */
    const APP_SERVICES = 'metagist.services';
    
    /**
     * App key under which the service consumers are registered.
     * 
     * @var string
     */
    const APP_CONSUMERS = 'metagist.consumers';
    
    /**
     * App key under which the worker config is registered.
     * 
     * @var string
     */
    const APP_WORKER_CONFIG = 'metagist.worker.config';
    
    /**
     * App key under which the server config is registered.
     * 
     * @var string
     */
    const APP_SERVER_CONFIG = 'metagist.server.config';
    
    /**
     * Key where a monolog instance must be present to enable logging.
     * 
     * @var string
     */
    const APP_MONOLOG = 'monolog';
    
    /**
     * app instance.
     * 
     * @var \Silex\Application
     */
    protected $app;
    
    /**
     * Register Guzzle with Silex
     *
     * @param Application $app Application to register with
     */
    public function register(Application $app)
    {
        $this->app = $app;

        $this->registerApiCallback($app);
    }
    
    /**
     * Registers itself under "metagist.api".
     * 
     * @param \Silex\Application $app
     * @return void
     */
    protected function registerApiCallback(Application $app)
    {
        $that = $this;
        $app[self::API] = $app->share(function () use ($that) {
            return $that;
        });
    }
    
    /**
     * Returns a guzzle service client instance.
     * 
     * @param string $name
     * @return \Guzzle\Service\ClientInterface
     * @throws \Guzzle\Service\Exception\ServiceNotFoundException
     */
    protected function buildService($name, array $config)
    {
        if (!array_key_exists('consumer_key', $config)) {
            throw new Exception('OAuth consumer key not configured', 500);
        }
        
        if (!array_key_exists('consumer_secret', $config)) {
            throw new Exception('OAuth consumer secret not configured', 500);
        }
        
        $builder = ServiceBuilder::factory($this->app[self::APP_SERVICES]);
        $client  = $builder->get($name, $config);
        $client->setDescription(
            ServiceDescription::factory($this->getDefaultDescription($name))
        );
        
        /*
         * add plugin for twolegged oauth 
         */
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin($config);
        $client->addSubscriber($plugin);
        
        /*
         * add logger plugin
         */
        if (isset($this->app[self::APP_MONOLOG]) && $this->app[self::APP_MONOLOG] instanceof \Monolog\Logger) {
            $plugin = new \Guzzle\Plugin\Log\LogPlugin(
                new \Guzzle\Log\MonologLogAdapter($this->app[self::APP_MONOLOG])
            );
            $client->addSubscriber($plugin);
        }
        
        /*
         * add json schema validation plugin
         */
        $client->addSubscriber($this->getSchemaValidator());
        
        return $client;
    }
    
    /**
     * Returns the path to the default json service description.
     * 
     * @param string $name
     * @return string
     */
    protected function getDefaultDescription($name)
    {
        return realpath(__DIR__ . '/../../../services/' . $name . '.json');
    }
    
    /**
     *  Provides a worker client.
     * 
     * @return \Metagist\Api\WorkerInterface
     * @throws \Metagist\Api\Exception
     */
    public function worker()
    {
        if (!isset($this->app[self::APP_WORKER_CONFIG])) {
            throw new Exception('Worker is not configured.', 500);
        }
        
        return $this->buildService('Worker', $this->app[self::APP_WORKER_CONFIG]);
    }
    
    /**
     * Provides a server client.
     * 
     * @return \Metagist\Api\ServerInterface
     * @throws \Metagist\Api\Exception
     */
    public function server()
    {
        if (!isset($this->app[self::APP_SERVER_CONFIG])) {
            throw new Exception('Server is not configured.', 500);
        }
        
        return $this->buildService('Server', $this->app[self::APP_SERVER_CONFIG]);
    }
    
    /**
     * Performs two-legged oauth validation.
     * 
     * @param string $message raw incoming message.
     * @return string the consumer key of the sender.
     */
    public function validateRequest($message)
    {
        if (!isset($this->app[self::APP_CONSUMERS])) {
            throw new Exception('Service consumers are not configured.', 500);
        }
        
        $validator = new OAuthValidator($this->app[self::APP_CONSUMERS]);
        $validator->validateRequest($message);
        return $validator->getConsumerKey();
    }
    
    /**
     * Returns a serializer instance.
     * 
     * @return \JMS\Serializer\SerializerInterface
     * @todo might be replaced by guzzle features
     */
    public function getSerializer()
    {
        $builder = SerializerBuilder::create();
        $builder->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy());
        $serializer = $builder->build();
        return $serializer;
    }
    
    /**
     * Returns a schema validator instance.
     * 
     * @return \Metagist\Api\Validation\Plugin\SchemaValidator
     */
    public function getSchemaValidator()
    {
        $config = array(
            'scan' => null,
            'package'  => __DIR__ . '/../../../services/api.package.json',
            'pushInfo' => __DIR__ . '/../../../services/api.pushInfo.json'
        );
        $resolver = new Validation\SchemaResolver($config);
        return new Validation\Plugin\SchemaValidator($resolver);
    }

    public function boot(Application $app)
    {
    }
}
<?php
namespace Metagist\Api;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Description\ServiceDescription;

/**
 * Silex service provider which registers metagist api clients.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @link https://github.com/guzzle/guzzle-silex-extension/blob/master/GuzzleServiceProvider.php
 */
class ServiceProvider implements ServiceProviderInterface
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
        if (!array_key_exists('description', $config)) {
            throw new Exception('Service description not configured', 500);
        }
        
        if (!array_key_exists('consumer_key', $config)) {
            throw new Exception('OAuth consumer key not configured', 500);
        }
        
        if (!array_key_exists('consumer_secret', $config)) {
            throw new Exception('OAuth consumer secret not configured', 500);
        }
        
        $builder = ServiceBuilder::factory($this->app[self::APP_SERVICES]);
        $client  = $builder->get($name, $config);
        $client->setDescription(ServiceDescription::factory($config['description']));
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin($config);
        $client->addSubscriber($plugin);
        
        if (isset($this->app[self::APP_MONOLOG]) && $this->app[self::APP_MONOLOG] instanceof \Monolog\Logger) {
            $plugin = new \Guzzle\Plugin\Log\LogPlugin(
                new \Guzzle\Log\MonologLogAdapter($this->app[self::APP_MONOLOG])
            );
            $client->addSubscriber($plugin);
        }
        
        return $client;
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

    public function boot(Application $app)
    {
    }
}
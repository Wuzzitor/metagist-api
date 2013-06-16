<?php
namespace Metagist\Api;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Service\Builder\ServiceBuilder;

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
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin($config);
        $client->addSubscriber($plugin);
        
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
        
        $config = $this->app[self::APP_WORKER_CONFIG];
        $client = $this->buildService('Worker', $config);
        
        
        return $client;
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
        
        $config = $this->app[self::APP_SERVER_CONFIG];
        $client = new ServerClient($config['base_url'], $config);
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin($config);
        $client->addSubscriber($plugin);
        
        return $client;
    }

    public function boot(Application $app)
    {
    }
}
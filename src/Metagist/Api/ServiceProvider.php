<?php

namespace Metagist\Api;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Http\Message\RequestInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider;

/**
 * Silex service provider which registers metagist api clients.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @link https://github.com/guzzle/guzzle-silex-extension/blob/master/GuzzleServiceProvider.php
 */
class ServiceProvider extends Factory implements ServiceProviderInterface
{
    /**
     * Register Guzzle with Silex
     *
     * @param Application $app Application to register with
     */
    public function register(Application $app)
    {
        $config = array(
            self::APP_CONSUMERS => $app[self::APP_CONSUMERS],
            self::APP_SERVICES  => $app[self::APP_SERVICES],
        );
        if (isset($app[self::APP_WORKER_CONFIG])) {
            $config[self::APP_WORKER_CONFIG] = $app[self::APP_WORKER_CONFIG];
        }
        if (isset($app[self::APP_SERVER_CONFIG])) {
            $config[self::APP_SERVER_CONFIG] = $app[self::APP_SERVER_CONFIG];
        }
        $config['dispatcher'] = $app['dispatcher'];
        $this->setConfig($config);
        
        $this->registerApiCallback($app);
        $this->registerApiAuthenticationListener($app);
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
     * Dispatcher an oauth request validation event.
     * 
     * @param \Guzzle\Http\Message\RequestInterface $request
     */
    public function validateRequest(RequestInterface $request)
    {
        if (!isset($this->config['dispatcher'])) {
            throw new Exception('Dispatcher instance not available in config.');
        }
        
        $validator = new RequestValidator($this->config['dispatcher']);
        $validator->validateRequest($request);
    }
    
    /**
     * Registers an authentication listener for api-based authentication.
     * 
     * @param \Silex\Application $app
     */
    protected function registerApiAuthenticationListener(Application $app)
    {
        $that = $this;
        $app['security.authentication_listener.factory.api'] = $app->protect(function ($name, $options) use ($that, $app) {
            // define the authentication provider object
            $provider = $this->getPreAuthenticatedProvider();
            $app['security.authentication_provider.' . $name . '.api'] = $app->share(function () use ($provider) {
                return $provider;
            });

            // define the authentication listener object
            $subscriber = $this->getAuthenticationListener($app['security'], $app['security.authentication_manager'], $app['monolog']);
            $app['security.authentication_listener.' . $name . '.api'] = $app->share(function () use ($subscriber) {
                $app['dispatcher']->addSubscriber($subscriber);
                return $subscriber;
            });

            return array(
                // the authentication provider id
                'security.authentication_provider.' . $name . '.api',
                // the authentication listener id
                'security.authentication_listener.' . $name . '.api',
                // the entry point id
                null,
                // the position of the listener in the stack
                'pre_auth'
            );
        });
        
        //enable the api firewall for the /api path in requests
        if (isset($app['security.firewalls'])) {
            $firewalls = $app['security.firewalls'];
            $firewalls['api'] = array(
                'pattern' => '^/api',
                'api' => true,
                'opauth' => false,
            );
            $app['security.firewalls'] = $firewalls;
        }
    }

    public function boot(Application $app)
    {
        
    }

}
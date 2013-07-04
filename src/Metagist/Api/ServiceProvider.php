<?php

namespace Metagist\Api;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Http\Message\RequestInterface;
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
        $client = $builder->get($name, $config);
        $client->setDescription(
            ServiceDescription::factory($this->getDefaultDescription($name))
        );
        $eventDispatcher = $client->getEventDispatcher();

        /*
         * add plugin for twolegged oauth 
         */
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin($config);
        $eventDispatcher->addSubscriber($plugin);

        /*
         * add logger plugin
         */
        if (isset($this->app[self::APP_MONOLOG]) && $this->app[self::APP_MONOLOG] instanceof \Monolog\Logger) {
            $plugin = new \Guzzle\Plugin\Log\LogPlugin(
                new \Guzzle\Log\MonologLogAdapter($this->app[self::APP_MONOLOG])
            );
            $eventDispatcher->addSubscriber($plugin);
        }


        /*
         * add json schema validation plugin
         */
        $eventDispatcher->addSubscriber($this->getSchemaValidator());

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
     * @param RequestInterface $message raw incoming message.
     * @return string the consumer key of the sender.
     */
    public function validateRequest(RequestInterface $request)
    {
        if (!isset($this->app[self::APP_CONSUMERS])) {
            throw new Exception('Service consumers are not configured.', 500);
        }
        $dispatcher = $this->app['dispatcher']; /* @var $dispatcher Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $event = new \Symfony\Component\EventDispatcher\GenericEvent($request);
        $dispatcher->dispatch(AuthenticationListener::EVENT_INCOMING_REQUEST, $event);
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
            'basepath' => __DIR__ . '/../../../services/',
            'mapping' => array(
                'scan' => null,
                'package' => __DIR__ . '/../../../services/api.package.json',
                'pushInfo' => __DIR__ . '/../../../services/api.pushInfo.json'
            )
        );
        $resolver = new Validation\SchemaResolver($config);
        return new Validation\Plugin\SchemaValidator($resolver);
    }

    /**
     * Returns a request instance containing the incoming data.
     * 
     * @link http://stackoverflow.com/questions/11990388/request-headers-bag-is-missing-authorization-header-in-symfony-2
     * @return \Guzzle\Http\Message\RequestInterface
     * @todo Symfony Request is used to get request as string, can maybe be replaced by Guzzle
     */
    public function getIncomingRequest($message = null)
    {
        if ($message === null) {
            $message = $this->getIncomingRequestMessage();
        }

        $factory = new \Guzzle\Http\Message\RequestFactory();
        $request = $factory->fromMessage($message);

        //fix missing https in url
        $url = $request->getUrl();
        if (isset($_SERVER['HTTPS']) && strpos($url, 'https') === false) {
            $request->setUrl(str_replace('http', 'https', $url));
        }
        return $request;
    }

    /**
     * Recreates the incoming request message, returns it as plain text.
     * 
     * @return string
     */
    protected function getIncomingRequestMessage()
    {
        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

        //add authorization header
        if (!$request->headers->has('Authorization') && function_exists('apache_request_headers')) {
            $all = apache_request_headers();
            if (isset($all['Authorization'])) {
                $authHeader = $all['Authorization'];
                if (strpos($authHeader, 'OAuth ') === false) {
                    $authHeader = 'OAuth ' . $authHeader;
                }
                $request->headers->set('Authorization', $authHeader);
            }
        }

        return $request->__toString();
    }

    /**
     * Registers an authentication listener for api-based authentication.
     * 
     * @param \Silex\Application $app
     */
    protected function registerApiAuthenticationListener(Application $app)
    {
        $app['security.authentication_listener.factory.api'] = $app->protect(function ($name, $options) use ($app) {
            // define the authentication provider object
            $app['security.authentication_provider.' . $name . '.api'] = $app->share(function () use ($app) {
                $consumers = $this->application[Api\ServiceProvider::APP_CONSUMERS];
                $users = array();
                foreach (array_keys($consumers) as $consumer) {
                    $users[$consumer] = array('enabled' => true);
                }
                $inMemoryProvider = new \Symfony\Component\Security\Core\User\InMemoryUserProvider($users);
                return new PreAuthenticatedAuthenticationProvider(
                    $inMemoryProvider,
                    new UserChecker(),
                    'api'
                );
            });

            // define the authentication listener object
            $app['security.authentication_listener.' . $name . '.api'] = $app->share(function () use ($app) {
                return new AuthenticationListener(
                    $app['security'], $app['security.authentication_manager'], $app['monolog']
                );
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
    }

    public function boot(Application $app)
    {
        
    }

}
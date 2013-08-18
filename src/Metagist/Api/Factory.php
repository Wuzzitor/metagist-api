<?php
/**
 * ApiProvider
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @package metagist-api
 */
namespace Metagist\Api;

use Guzzle\Service\Builder\ServiceBuilder;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Http\Message\RequestInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Psr\Log\LoggerInterface;
    
/**
 * Silex service provider which registers metagist api clients.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class Factory implements FactoryInterface
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
     * configuration
     * 
     * @var array
     */
    protected $config = array();

    /**
     * Set the configuration.
     * 
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    /**
     * Returns a guzzle service client instance.
     * 
     * @param string $name
     * @param array  $config
     * @return \Guzzle\Service\ClientInterface
     * @throws \Guzzle\Service\Exception\ServiceNotFoundException
     */
    protected function buildService($name, array $config)
    {
        if (!array_key_exists('consumer_key', $config)) {
            throw new Exception('OAuth consumer_key not configured', 500);
        }

        if (!array_key_exists('consumer_secret', $config)) {
            throw new Exception('OAuth consumer_secret not configured', 500);
        }

        $builder = ServiceBuilder::factory($this->config[self::APP_SERVICES]);
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
        if (isset($this->config[self::APP_MONOLOG]) && $this->config[self::APP_MONOLOG] instanceof \Monolog\Logger) {
            $plugin = new \Guzzle\Plugin\Log\LogPlugin(
                new \Guzzle\Log\MonologLogAdapter($this->config[self::APP_MONOLOG])
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
    public function getWorkerClient()
    {
        if (!isset($this->config[self::APP_WORKER_CONFIG])) {
            throw new Exception('Worker is not configured.', 500);
        }

        return $this->buildService('Worker', $this->config[self::APP_WORKER_CONFIG]);
    }

    /**
     * Provides a server client.
     * 
     * @return \Metagist\Api\ServerInterface
     * @throws \Metagist\Api\Exception
     */
    public function getServerClient()
    {
        if (!isset($this->config[self::APP_SERVER_CONFIG])) {
            throw new Exception('Server is not configured.', 500);
        }

        return $this->buildService('Server', $this->config[self::APP_SERVER_CONFIG]);
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
     * Creates an oauth validator.
     * 
     * @return \Metagist\Api\OAuthValidator
     * @throws Exception
     */
    public function getOauthValidator()
    {
        if (!isset($this->config[self::APP_CONSUMERS])) {
            throw new Exception('Service consumers are not configured.', 500);
        }
        
        return new OAuthValidator($this->config[self::APP_CONSUMERS]);
    }
    
    /**
     * Factory method to create the authentication listener.
     * 
     * @param \Symfony\Component\Security\Core\SecurityContext $context
     * @param \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $manager
     * @param \Psr\Log\LoggerInterface $logger
     * @return \Metagist\Api\AuthenticationListener
     */
    public function getAuthenticationListener(
        SecurityContext $context,
        AuthenticationManagerInterface $manager,
        LoggerInterface $logger
    ) {
        $listener = new AuthenticationListener(
            $context,
            $manager,
            $this->getOauthValidator(),
            $logger
        );
        return $listener;
    }
    
    /**
     * Creates a preauthenticated auth provider for symfony security.
     * 
     * @return \Symfony\Component\Security\Core\Authentication\Provider\PreAuthenticatedAuthenticationProvider
     */
    public function getPreAuthenticatedProvider()
    {
        return new PreAuthenticatedAuthenticationProvider(
            new UserProvider($this->config[self::APP_CONSUMERS]),
            new \Symfony\Component\Security\Core\User\UserChecker(),
            'api'
        );
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
}
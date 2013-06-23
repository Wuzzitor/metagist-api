<?php
namespace Metagist\Api\Test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Metagist\Api\ServiceProvider;

/**
 * Provides a signed request for testing purposes.
 * 
 * 
 */
class MessageProvider implements EventSubscriberInterface
{
    const CONSUMER_KEY = 'test1';
    const CONSUMER_SECRET = 'test';
    
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
     * the request message
     * 
     * @var string
     */
    private $message;
    
    /**
     * 
     * 
     * 
     */
    public function __construct()
    {
        $this->serviceProvider = new ServiceProvider();
        $this->app = new \Silex\Application();
        
        $this->app[ServiceProvider::APP_SERVICES] = realpath(__DIR__ . '/../../../../services/testservices.json');
        $this->app[ServiceProvider::APP_CONSUMERS] = array(
            self::CONSUMER_KEY => 'test'
        );
        $this->app[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://localhost',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
        );
        $this->serviceProvider->register($this->app);
    }
    
    /**
     * Returns the raw request message for the "scan" request.
     * 
     * @return string
     */
    public function getMessage ($consumerKey = self::CONSUMER_KEY, $consumerSecret = self::CONSUMER_SECRET)
    {
        $config = $this->app[ServiceProvider::APP_WORKER_CONFIG];
        $config['consumer_key'] = $consumerKey;
        $config['consumer_secret'] = $consumerSecret;
        $this->app[ServiceProvider::APP_WORKER_CONFIG]= $config;
        
        $worker = $this->serviceProvider->worker();
        $worker->addSubscriber($this);
        try {
            $worker->scan('authorname', 'packagename');
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $exception) {
            /*
             * do nothing.
             * @see dump()
             */
        }
        
        return $this->message;
    }
    
    /**
     * Registers "dump" as callback 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'request.complete' => array("dump", -1)
        );
    }

    /**
     * Callback.
     * 
     * @param \Symfony\Component\EventDispatcher\Event $event
     */
    public function dump(\Symfony\Component\EventDispatcher\Event $event)
    {
        $request = $event['request'];
        /* @var $request \Guzzle\Http\Message\Request */
        $this->message = $request->__toString();
    }
}
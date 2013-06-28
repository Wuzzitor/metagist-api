<?php
namespace Metagist\Api\Test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Metagist\Api\ServiceProvider;
use Guzzle\Common\Event;

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
     * @var \Metagist\Api\ServiceProvider
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
     * Fake timestamp
     * @var type 
     */
    private $timestamp;
    
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
        } catch (\Metagist\Api\Exception $exception) {
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
            'request.complete' => array("dump", -1),
            'request.before_send' => array('modifyTimestamp', -999)
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
    
    /**
     * Set a fake timestamp to use for signing the request.
     * 
     * @param type $timestamp
     */
    public function setFakeTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }
    
    /**
     * Callback.
     * 
     * @param \Metagist\Api\Test\Event $event
     */
    public function modifyTimestamp(Event $event)
    {
        if ($this->timestamp !== null) {
            $event['timestamp'] = $this->timestamp;
        }
    }
}
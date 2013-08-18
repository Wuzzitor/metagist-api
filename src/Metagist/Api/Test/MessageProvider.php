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
     * configuration array
     * 
     * @var array
     */
    private $config;
    
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
        
        $this->config[ServiceProvider::APP_SERVICES] = realpath(__DIR__ . '/../../../../services/testservices.json');
        $this->config[ServiceProvider::APP_CONSUMERS] = array(
            self::CONSUMER_KEY => 'test'
        );
        $this->config[ServiceProvider::APP_WORKER_CONFIG] = array(
            'base_url' => 'http://localhost',
            'description' => realpath(__DIR__ . '/../../../../services/Worker.json'),
        );
        $this->config[ServiceProvider::APP_SERVER_CONFIG] = array(
            'base_url' => 'http://localhost',
            'description' => realpath(__DIR__ . '/../../../../services/Server.json'),
        );
        $this->serviceProvider->setConfig($this->config);
    }
    
    /**
     * Returns the raw request message for the "scan" request.
     * 
     * @return string
     */
    public function getMessage ($consumerKey = self::CONSUMER_KEY, $consumerSecret = self::CONSUMER_SECRET)
    {
        $worker = $this->getWorker($consumerKey, $consumerSecret);
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
     * Returns a configured worker.
     * 
     * @param type $consumerKey
     * @param type $consumerSecret
     * @return \Metagist\Api\WorkerInterface
     */
    protected function getWorker($consumerKey = self::CONSUMER_KEY, $consumerSecret = self::CONSUMER_SECRET)
    {
        $config[ServiceProvider::APP_WORKER_CONFIG] = $this->config[ServiceProvider::APP_WORKER_CONFIG];
        $config[ServiceProvider::APP_WORKER_CONFIG]['consumer_key'] = $consumerKey;
        $config[ServiceProvider::APP_WORKER_CONFIG]['consumer_secret'] = $consumerSecret;
        $this->serviceProvider->setConfig($config);
        
        $worker = $this->serviceProvider->getWorkerClient();
        
        return $worker;
    }
    
    /**
     * Returns the raw request message for the "pushInfo" request.
     * 
     * @return string
     */
    public function getPushInfoMessage()
    {
        $config[ServiceProvider::APP_SERVER_CONFIG] = $this->config[ServiceProvider::APP_SERVER_CONFIG];
        $config[ServiceProvider::APP_SERVER_CONFIG]['consumer_key'] = self::CONSUMER_KEY;
        $config[ServiceProvider::APP_SERVER_CONFIG]['consumer_secret'] = self::CONSUMER_SECRET;
        $this->serviceProvider->setConfig($config);
        
        $server = $this->serviceProvider->getServerClient();
        $server->addSubscriber($this);
        
        try {
            $server->pushInfo('author', 'name', \Metagist\Metainfo::fromValue('testInteger', 1, '0.1.1'));
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
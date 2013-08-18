<?php
namespace Metagist\Api;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Guzzle\Http\Message\RequestInterface;

/**
 * Two legged oauth request validator.
 * 
 */
class RequestValidator
{
    /**
     * event dispatcher
     * 
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface  
     */
    private $dispatcher;
    
    /**
     * Inject an event dispatcher
     * 
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * Performs two-legged oauth validation.
     * 
     * @param RequestInterface $message raw incoming message.
     */
    public function validateRequest(RequestInterface $request)
    {
        $event = new \Symfony\Component\EventDispatcher\GenericEvent($request);
        $this->dispatcher->dispatch(AuthenticationListener::EVENT_INCOMING_REQUEST, $event);
    }
}
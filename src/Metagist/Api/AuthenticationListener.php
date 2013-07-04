<?php
namespace Metagist\Api;

use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Symfony\Component\EventDispatcher\GenericEvent;
use \Symfony\Component\Security\Core\SecurityContext;
use \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use \Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use \Symfony\Component\Security\Http\Firewall\ListenerInterface;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use \Psr\Log\LoggerInterface;

/**
 * Listens for api authentication events.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class AuthenticationListener implements EventSubscriberInterface, ListenerInterface
{
    /**
     * name of the successful authentication event
     * 
     * @var string
     */
    const EVENT_AUTHENTICATED = 'metagist.api.authenticated';
    
    /**
     * name of the incoming request event
     * 
     * @var string
     */
    const EVENT_INCOMING_REQUEST = 'metagist.api.incomingRequest';
    
    /**
     * context
     * @var \Symfony\Component\Security\Core\SecurityContext 
     */
    private $context;
    
    /**
     * auth manager
     * @var \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager
     */
    private $manager;
    
    /**
     * oauth request validator
     * 
     * @var \Metagist\Api\OAuthValidator
     */
    private $validator;
    
    /**
     * logger
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;
   
    /**
     * Constructor
     * 
     * @param \Symfony\Component\Security\Core\SecurityContext $context
     * @param \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface $manager
     * @param \Metagist\Api\OAuthValidator $validator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        SecurityContext $context,
        AuthenticationManagerInterface $manager,
        OAuthValidator $validator,
        LoggerInterface $logger
    ) {
        $this->context        = $context;
        $this->manager        = $manager;
        $this->validator      = $validator;
        $this->logger         = $logger;
    }
    
    /**
     * Event this listener subscribes to.
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            self::EVENT_INCOMING_REQUEST => 'onIncomingRequest',
            self::EVENT_AUTHENTICATED    => 'onAuthenticationSuccess',
        );
    }
    
    /**
     * Reacts on an incoming request, validates it.
     * 
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @return void
     */
    public function onIncomingRequest(GenericEvent $event)
    {
        $request = $event->getSubject();
        $this->validator->validateRequest($request);
        
        $successEvent = new GenericEvent($this->validator->getConsumerKey());
        $event->getDispatcher()->dispatch(self::EVENT_AUTHENTICATED, $successEvent);
    }

    /**
     * Reacts on a successful authentication, creates a fake user.
     * 
     * @param GenericEvent $event
     */
    public function onAuthenticationSuccess(GenericEvent $event)
    {
        $consumerKey = $event->getSubject();
        $user        = new \Metagist\User($consumerKey, \Metagist\User::ROLE_SYSTEM);
        
        $token = new PreAuthenticatedToken($user, '', 'api');
        $this->manager->authenticate($token);
        $this->context->setToken($token);
        
        $this->logger->info("User authenticated for consumer " . $user->getUsername());
        return $user;
    }
    
    public function handle(GetResponseEvent $event)
    {
    }
}
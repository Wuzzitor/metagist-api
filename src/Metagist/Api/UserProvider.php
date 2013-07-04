<?php
namespace Metagist\Api;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * User Provider for API consumers.
 * 
 * Works much like the InMemoryuserProvider.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class UserProvider implements UserProviderInterface
{
    /**
     * assoc array of consumer keys => settings
     * 
     * @var array
     */
    private $consumers;
    
    /**
     * Factory method.
     * 
     * @param array $consumers
     * @return \Metagist\Api\UserProvider
     * @see Api\ServiceProvider::APP_CONSUMERS
     */
    public static function create(array $consumers)
    {
        $users = array();
        foreach (array_keys($consumers) as $consumer) {
            $users[$consumer] = array(
                'enabled' => true,
                'roles'   => array(\Metagist\User::ROLE_SYSTEM)
            );
        }
        
        return new UserProvider($users);
    }
    
    /**
     * Constructor required the registered consumers.
     * 
     * @param array $consumers
     */
    public function __construct(array $consumers)
    {
        $this->consumers = $consumers;
    }
    
    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $class === 'Metagist\User';
    }
    
    /**
     * Loads a user.
     * 
     * @param string|\Metagist\User $username
     * @return \Metagist\User
     */
    public function loadUserByUsername($username)
    {
        if ($username instanceof \Metagist\User) {
            $username = $username->getUsername();
        }
        $this->assertExists($username);
        $user = new \Metagist\User($username, \Metagist\User::ROLE_SYSTEM);
        return $user;
    }

    /**
     * Refresh.
     * 
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function refreshUser(UserInterface $user)
    {
        $this->assertExists($user->getUsername());
        return $user;
    }
    
/**
     * Ensures that the username is registered as api consumer.
     * 
     * @param string $username
     * @throws UnsupportedUserException
     */
    protected function assertExists($username)
    {
        if (!array_key_exists($username, $this->consumers)) {
            throw new UnsupportedUserException(
                'Unknown consumer "' . $username . '". Registered consumers: ' . implode(',', array_keys($this->consumers))
            );
        }
    }
}
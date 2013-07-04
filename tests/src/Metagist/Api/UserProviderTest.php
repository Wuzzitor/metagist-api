<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the worker client
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class UserProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\UserProvider
     */
    private $provider;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->provider = new \Metagist\Api\UserProvider(array('worker' => 'test'));
    }
    
    /**
     * Ensures implements the symfony interface
     */
    public function testInstanceOfUserProvider()
    {
        $this->assertInstanceOf("\Symfony\Component\Security\Core\User\UserProviderInterface", $this->provider);
    }
    
    /**
     * Ensures the factory method creates an instance.
     */
    public function testCreate()
    {
        $provider = \Metagist\Api\UserProvider::create(array('worker' => 'test'));
        $this->assertInstanceOf("\Metagist\Api\UserProvider", $provider);
    }
    
    /**
     * Ensures normal behaviour.
     */
    public function testLoadUserByName()
    {
        $other = $this->provider->loadUserByUsername('worker');
        $this->assertEquals('worker', $other->getUsername());
        $this->assertContains(\Metagist\User::ROLE_SYSTEM, $other->getRoles());
    }
    
    /**
     * Tests undocumented behaviour.
     */
    public function testLoadUserByUser()
    {
        $user = new \Metagist\User('worker');
        $other = $this->provider->loadUserByUsername($user);
        $this->assertEquals('worker', $other->getUsername());
        $this->assertContains(\Metagist\User::ROLE_SYSTEM, $other->getRoles());
    }
    
    /**
     * Ensures an Symfony\Component\Security\Core\Exception\UnsupportedUserException is thrown.
     */
    public function testLoadUserByNameFails()
    {
        $this->setExpectedException("\Symfony\Component\Security\Core\Exception\UnsupportedUserException");
        $this->provider->loadUserByUsername('test');
    }
    
    /**
     * Ensures that the same object is returned on refresh.
     */
    public function testRefresh()
    {
        $user = new \Metagist\User('worker');
        $this->assertSame($user, $this->provider->refreshUser($user));
    }
    
    /**
     * Ensures that the same object is returned on refresh.
     */
    public function testRefreshFails()
    {
        $user = new \Metagist\User('test');
        $this->setExpectedException("\Symfony\Component\Security\Core\Exception\UnsupportedUserException");
        $this->provider->refreshUser($user);
    }
}
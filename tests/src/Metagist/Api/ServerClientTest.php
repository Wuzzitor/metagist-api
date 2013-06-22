<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the server client
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ServerClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\ServerClient
     */
    private $client;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->client = new \Metagist\Api\ServerClient();
        $file = realpath(__DIR__ . '/../../../../services/Server.json');
        $this->client->setDescription(\Guzzle\Service\Description\ServiceDescription::factory($file));
    }
    
    /**
     * Ensures the client implements \Metagist\Api\ServerInterface
     */
    public function testImplementsInterface()
    {
        $this->assertInstanceOf("\Metagist\Api\ServerInterface", $this->client);
    }
    
    /**
     * Ensures the package command works as expected.
     */
    public function testPackage()
    {
        try {
            $this->client->package('author', 'name');
        } catch (\Guzzle\Http\Exception\CurlException $exception) {
            $this->assertContains('package/author/name', $exception->getMessage());
        }
    }
    
    /**
     * Ensures the package command works as expected.
     */
    public function testPushInfo()
    {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new \Guzzle\Http\Message\Response(200));
        $this->client->addSubscriber($plugin);
        
        $info = \Metagist\MetaInfo::fromValue('test/test', 'avalue');
        $this->client->pushInfo('author', 'name', $info);
        
        
        $requests = $plugin->getReceivedRequests();
        $this->assertNotEmpty($requests);
        $request = current($requests); /* @var $request \Guzzle\Http\Message\Request */
        $this->assertContains('pushInfo/author/name', $request->getPath());
        
        $this->assertContains('{"category":"test","group":"test","value":"avalue"}', $request->__toString());
    }
}
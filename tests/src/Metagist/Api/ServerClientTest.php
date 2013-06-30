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
    public function testPackagePathContainsAuthorAndName()
    {
        try {
            $this->client->package('author', 'name');
        } catch (\Guzzle\Http\Exception\CurlException $exception) {
            $this->assertContains('package/author/name', $exception->getMessage());
        }
    }
    
    /**
     * Ensures a package instance with metainfos is returned.
     */
    public function testReturnsPackageInstance()
    {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $response = new \Guzzle\Http\Message\Response(200);
        
        $body = $this->encodePackage($this->createDummyPackage());
        $response->setBody($body);
        $plugin->addResponse($response);
        $this->client->addSubscriber($plugin);
        
        $package = $this->client->package('author', 'name');
        $this->assertInstanceOf("\Metagist\Package", $package);
        $this->assertEquals('bonndan/test', $package->getIdentifier());
        $this->assertNotEmpty($package->getMetaInfos());
    }
    
    /**
     * Returns the json representation of a package.
     * 
     * @param \Metagist\Package $package
     * @return string
     */
    protected function encodePackage(\Metagist\Package $package)
    {
        $serviceProvider = new ServiceProvider();
        $serializer = $serviceProvider->getSerializer();
        return $serializer->serialize($package, 'json');
    }
    
    /**
     * Creates a dummy package.
     * 
     * @return \Metagist\Package
     */
    protected function createDummyPackage()
    {
        $package = new \Metagist\Package('bonndan/test');
        
        $collection = new \Doctrine\Common\Collections\ArrayCollection();
        $collection->add(\Metagist\MetaInfo::fromValue('testInteger', 1));
        $package->setMetaInfos($collection);
        return $package;
    }
    
    /**
     * Ensures the package command works as expected.
     */
    public function testPushInfo()
    {
        $plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $plugin->addResponse(new \Guzzle\Http\Message\Response(200));
        $this->client->addSubscriber($plugin);
        
        $info = \Metagist\MetaInfo::fromValue('test', 'avalue', '1.0.0');
        $this->client->pushInfo('author', 'name', $info);
        
        
        $requests = $plugin->getReceivedRequests();
        $this->assertNotEmpty($requests);
        $request = current($requests); /* @var $request \Guzzle\Http\Message\Request */
        $this->assertContains('pushInfo/author/name', $request->getPath());
        
        $this->assertContains('{"group":"test","version":"1.0.0","value":"avalue"}', $request->__toString());
    }
}
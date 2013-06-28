<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the worker client
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class WorkerClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\WorkerClient
     */
    private $client;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->client = new \Metagist\Api\WorkerClient();
        $file = realpath(__DIR__ . '/../../../../services/Worker.json');
        $this->client->setDescription(\Guzzle\Service\Description\ServiceDescription::factory($file));
    }
    
    /**
     * Ensures the client implements \Metagist\Api\WorkerInterface
     */
    public function testImplementsInterface()
    {
        $this->assertInstanceOf("\Metagist\Api\WorkerInterface", $this->client);
    }
    
    /**
     * Ensures the scan command works as expected.
     */
    public function testScan()
    {
        try {
            $this->client->scan('author', 'name');
        } catch (\Guzzle\Http\Exception\CurlException $exception) {
            $this->assertContains('scan/author/name', $exception->getMessage());
        }
    }
    
    /**
     * Ensures guzzles bad response exception are caught.
     */
    public function testScanCatchesBadResponseExceptions()
    {
        $mock = new \Guzzle\Plugin\Mock\MockPlugin();
        $mock->addResponse(new \Guzzle\Http\Message\Response(403));
        $this->client->addSubscriber($mock);

        $this->setExpectedException("\Metagist\Api\Exception");
        $this->client->scan('test', 'test');
    }
}
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
    public function testRegistersApiCallback()
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
}
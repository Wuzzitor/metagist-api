<?php
namespace Metagist\Api;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the oauth validator
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class OAuthValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\OAuthValidator
     */
    private $validator;
    
    /**
     * service provider
     * 
     * @var \Metagist\Api\ServiceProvider
     */
    private $serviceProvider;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        
        $this->serviceProvider = new ServiceProvider();
        
        $this->validator = new \Metagist\Api\OAuthValidator(
            array(
                'allowedConsumer' => 'allowedSecret',
                'worker' => 'dev-master',
            )
        );
    }
    
    /**
     * Ensures an exception is thrown if the consumer key is not known.
     */
    public function testFailsForUnknownConsumer()
    {
        $this->setExpectedException("\Metagist\Api\Exception", 'Unknown consumer');
        $request = $this->createRequest('test');
        $this->validator->validateRequest($request);
    }
    
    /**
     * Ensures an exception is thrown if the signature is not valid.
     */
    public function testFailsForWrongSignature()
    {
        $message = $this->createRequest();
        $factory = new \Guzzle\Http\Message\RequestFactory();
        $request = $factory->fromMessage($message);
        
        $params    = $this->validator->getAuthorizationParams($request);
        $signature = $params['oauth_signature'];
        $message   = str_replace($signature, 'fake-sig', $message);
        $request   = $this->serviceProvider->getIncomingRequest($message);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'Signature mismatch');
        $this->validator->validateRequest($request);
    }
    
    /**
     * Ensures an exception is thrown if the request age has exceeded.
     */
    public function testFailsForWrongTimestamp()
    {
        $message = $this->createRequest('allowedConsumer', 'allowedSecret', time() - 1000);
        $request = $this->serviceProvider->getIncomingRequest($message);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'Timestamp');
        $this->validator->validateRequest($request);
    }
    
    /**
     * This is a real incoming request.
     */
    public function testValidateRealRequest()
    {
        $_SERVER['HTTPS'] = 'on';
        
        //$this->markTestIncomplete('request is not valid');
        $message = 'POST /api/pushInfo/matthimatiker/molcomponents HTTP/1.1
Host: metagist.dev
User-Agent: Guzzle/3.7.0 curl/7.22.0 PHP/5.3.10-1ubuntu3.6
Content-Length: 119
Content-Type: application/json
Authorization: OAuth oauth_consumer_key="worker", oauth_nonce="e2436c4f9fbb23b52978cb5ef1a6a84bc55c6cdd", oauth_signature="qSko7f2aZxWiZOPoo3Q8SaHAOB0%3D", oauth_signature_method="HMAC-SHA1", oauth_timestamp="1372711591", oauth_version="1.0"

{"info":{"group":"repository","version":"dev-master","value":"https:\/\/github.com\/Matthimatiker\/MolComponents.git"}}';
        
        $serviceProvider = new ServiceProvider();
        $request = $serviceProvider->getIncomingRequest($message);
        
        $this->validator->validateRequest($request);
    }
    
    /**
     * Creates a signed request.
     * 
     * @param string $consumer
     * @param string $secret
     * @return string
     */
    protected function createRequest($consumer = 'allowedConsumer', $secret = 'allowedSecret', $timestamp = null)
    {
        $messageProvider = new \Metagist\Api\Test\MessageProvider();
        
        if ($timestamp != null) {
            $messageProvider->setFakeTimestamp($timestamp);
        }
        
        $message = $messageProvider->getMessage($consumer, $secret);
        
        return $this->serviceProvider->getIncomingRequest($message);
    }
}
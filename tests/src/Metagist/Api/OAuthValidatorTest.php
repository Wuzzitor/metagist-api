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
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $this->validator = new \Metagist\Api\OAuthValidator(
            array('allowedConsumer' => 'allowedSecret')
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
        
        $this->setExpectedException("\Metagist\Api\Exception", 'Signature mismatch');
        
        $this->validator->validateRequest($message);
    }
    
    /**
     * Ensures an exception is thrown if the request age has exceeded.
     */
    public function testFailsForWrongTimestamp()
    {
        $message = $this->createRequest('allowedConsumer', 'allowedSecret', time() - 1000);
        
        $this->setExpectedException("\Metagist\Api\Exception", 'Timestamp');
        $this->validator->validateRequest($message);
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
        
        return $messageProvider->getMessage($consumer, $secret);
    }
}
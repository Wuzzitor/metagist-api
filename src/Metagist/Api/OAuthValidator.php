<?php
namespace Metagist\Api;

use Guzzle\Http\Message\Request;

/**
 * Validates incoming OAuth requests.
 * 
 * Uses the Guzzle OAuth plugin to re-calculate signature hashes of requests.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @link http://code.google.com/p/oauth-php/wiki/ServerHowTo
 * @link http://developer.yahoo.com/blogs/ydn/two-legged-oauth-client-server-example-7922.html
 */
class OAuthValidator
{
    /**
     * key-secret list of service consumers.
     * 
     * @var array
     */
    protected $consumers = array();
    
    /**
     * Authorization params retrieved from the request.
     * 
     * @var array 
     */
    protected $authParams = array();
    
    /**
     * maximum age of the request in seconds (oauth_timestamp)
     * 
     * @var int
     */
    protected $maxAge = 60;
    
    /**
     * Pass the allowed consumers.
     * 
     * @param array $consumers
     */
    public function __construct(array $consumers)
    {
        $this->consumers = $consumers;
    }
    
    /**
     * Two-legged oauth request validation.
     * 
     * @param \Guzzle\Http\Message\Request $message
     * @throws Exception
     */
    public function validateRequest(Request $request)
    {
        $this->authParams = $this->getAuthorizationParams($request);
        
        $consumerKey = $this->getConsumerKey();
        if (!isset($this->consumers[$consumerKey])) {
            throw new Exception('Unknown consumer ' . $consumerKey, 401);
        }
        $secret = $this->consumers[$consumerKey];
        
        $plugin = new \Guzzle\Plugin\Oauth\OauthPlugin(
            array(
                'consumer_key'    => $consumerKey,
                'consumer_secret' => $secret
            )
        );
        $signature = $plugin->getSignature(
            $request,
            $this->getTimestamp(),
            $this->getNonce()
        );
        
        if ($signature != $this->getSignature()) {
            throw new Exception('Signature mismatch: computed ' . $signature . ', received ' . $this->getSignature(), 401);
        }
        
        $now = time();
        $timeDiff = abs($this->getTimestamp() - time());
        if ($timeDiff > $this->maxAge) {
            throw new Exception('Timestamp mismatch: ' .$this->getTimestamp() . ' : ' . $now, 401);
        }
    }
    
    /**
     * Parses the Authorization header into a map.
     * 
     * @param \Guzzle\Http\Message\Request $request
     * @return array
     * @throws Exception
     */
    public function getAuthorizationParams(\Guzzle\Http\Message\Request $request)
    {
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader === null) {
            throw new Exception('Missing Authorization header', 401);
        }
        
        $line = current($authHeader->toArray());
        if (strpos($line, 'OAuth ') === false) {
            throw new Exception('Unexpected Authorization header', 500);
        }
        
        $request->setHeader('Authorization', substr($line, strlen('OAuth ')));
        $oAuthParams = $request->getHeader('Authorization')->parseParams();
        
        $params = array();
        foreach ($oAuthParams as $data) {
            $params[key($data)] = current($data);
        }
        
        return $params;
    }
    
    /**
     * Returns the consumer key
     * 
     * @return string
     * @throws Exception
     */
    public function getConsumerKey()
    {
        if (empty($this->authParams['oauth_consumer_key'])) {
            throw new Exception('Could not find consumer in OAuth headers');
        }
        
        return $this->authParams['oauth_consumer_key'];
    }
    
    /**
     * Returns the signature, url-decoded.
     * 
     * @return string
     * @throws Exception
     */
    protected function getSignature()
    {
        if (empty($this->authParams['oauth_signature'])) {
            throw new Exception('Could not find signature in OAuth headers');
        }
        
        return urldecode($this->authParams['oauth_signature']);
    }
    
    /**
     * Returns the timestamp
     * 
     * @return string
     * @throws Exception
     */
    protected function getTimestamp()
    {
        if (empty($this->authParams['oauth_timestamp'])) {
            throw new Exception('Could not find timestamp in OAuth headers');
        }
        
        return $this->authParams['oauth_timestamp'];
    }
    
    /**
     * Returns the timestamp
     * 
     * @return string
     * @throws Exception
     */
    protected function getNonce()
    {
        if (empty($this->authParams['oauth_nonce'])) {
            throw new Exception('Could not find nonce in OAuth headers');
        }
        
        return $this->authParams['oauth_nonce'];
    }
}
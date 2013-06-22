<?php
namespace Metagist\Api;

use Guzzle\Service\Command\LocationVisitor\Request\JsonVisitor;
use JMS\Serializer\SerializerInterface;

/**
 * Request visitor for json encoding using JMS Serializer.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class JsonRequestVisitor extends JsonVisitor
{
    /**
     * serializer
     * 
     * @var \JMS\Serializer\SerializerInterface 
     */
    protected $serialzer;
    
    /**
     * Pass a serializer.
     * 
     * @param \JMS\Serializer\SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct();
        $this->serialzer = $serializer;
    }
    
    /**
     * Uses JMS serializer instead of json_encode.
     * 
     * @param \Guzzle\Service\Command\CommandInterface $command
     * @param \Guzzle\Http\Message\RequestInterface $request
     */
    public function after(\Guzzle\Service\Command\CommandInterface $command, \Guzzle\Http\Message\RequestInterface $request)
    {
        if (isset($this->data[$command])) {
            $request->setBody($this->serialzer->serialize($this->data[$command], 'json'));
            unset($this->data[$command]);
            // Don't overwrite the Content-Type if one is set
            if ($this->jsonContentType && !$request->hasHeader('Content-Type')) {
                $request->setHeader('Content-Type', $this->jsonContentType);
            }
        }
    }
}
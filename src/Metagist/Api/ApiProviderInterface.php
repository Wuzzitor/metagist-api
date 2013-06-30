<?php
namespace Metagist\Api;

/**
 * Interface for the API Provider
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface ApiProviderInterface
{
    /**
     * Returns the worker api.
     * 
     * @return \Metagist\Api\WorkerInterface
     * @throws \Metagist\Api\Exception on misconfiguration
     */
    public function worker();
    
    /**
     * Returns the server api.
     * 
     * @return \Metagist\Api\ServerInterface
     * @throws \Metagist\Api\Exception on misconfiguration
     */
    public function server();
    
    /**
     * Validates an incoming two-legged oauth request and returns the consumer
     * key on success.
     * 
     * @param string $message
     * @return string
     * @throws \Metagist\Api\Exception if the request is not valid.
     */
    public function validateRequest($message);
    
    /**
     * Returns a (de)serializer to handle json payloads.
     * 
     * @return \JMS\Serializer\SerializerInterface
     */
    public function getSerializer();
    
    /**
     * Returns a schema validator instance.
     * 
     * @return \Metagist\Api\Validation\Plugin\SchemaValidator
     */
    public function getSchemaValidator();
    
    /**
     * Returns a request instance containing the incoming data.
     * 
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getIncomingRequest();
}
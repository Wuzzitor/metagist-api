<?php
namespace Metagist\Api;

/**
 * Interface for the API Factory
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface FactoryInterface
{
    /**
     * Returns the client for the worker api.
     * 
     * @return \Metagist\Api\WorkerInterface
     * @throws \Metagist\Api\Exception on misconfiguration
     */
    public function getWorkerClient();
    
    /**
     * Returns the client for the server api.
     * 
     * @return \Metagist\Api\ServerInterface
     * @throws \Metagist\Api\Exception on misconfiguration
     */
    public function getServerClient();
    
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
     * @return \Guzzle\Http\Message\RequestInterface
     */
    public function getIncomingRequest();
}
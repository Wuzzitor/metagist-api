<?php
namespace Metagist\Api\Validation;

use Guzzle\Service\Command\OperationCommand;

/**
 * Class to resolve schema locations based on operation names (mapped by configuration).
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class SchemaResolver
{
    /**
     * request - schema mapping
     * 
     * @var array
     */
    private $config;
    
    /**
     * Pass the request-schema mapping.
     * 
     * basepath => string
     * mapping  => array ( array (operationName => schema path | null) )
     * 
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Fins the related schema for a request.
     * 
     * If no schema is mapping to the operation name, null is returned.
     * 
     * @param \Guzzle\Service\Command\OperationCommand $request
     * @return object|null
     * @throws \Metagist\Api\Validation\Exception
     */
    public function getSchemaForCommand(OperationCommand $command)
    {
        $opName = $command->getOperation()->getName();
        return $this->getSchemaForOperationName($opName);
    }
    
    /**
     * Finds and resolves a schema for an operation name.
     * 
     * If the schema is defined as null, validation is bypassed.
     * 
     * @param string $opName
     * @return object|null
     * @throws \Metagist\Api\Validation\Exception
     */
    public function getSchemaForOperationName($opName)
    {
        $path = $this->getSchemaPathForOperationName($opName);
        if ($path === null) {
            return null;
        }
        
        if (!file_exists($path)) {
            throw new Exception('Cannot load schema for operation ' . $opName, 404);
        }
        
        $retriever = new \JsonSchema\Uri\UriRetriever();
        $schema    = $retriever->retrieve('file://' . $path);

        // If you use $ref or if you are unsure, resolve those references here
        // This modifies the $schema object
        $refResolver = new \JsonSchema\RefResolver($retriever);
        $refResolver->resolve($schema, 'file://' . $this->config['basepath']);
        
        return $schema;
    }
    
    /**
     * Resolves a schema location by opration name.
     * 
     * @param string $opName
     * @return string
     * @throws \Metagist\Api\Validation\Exception
     */
    protected function getSchemaPathForOperationName($opName)
    {
        if (!array_key_exists($opName, $this->config['mapping'])) {
            throw new Exception('No schema registered for operation ' . $opName, 500);
        }
        
        return $this->config['mapping'][$opName];
    }
}
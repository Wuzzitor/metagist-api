<?php
namespace Metagist\Api\Validation;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the schema resolver
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class SchemaResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * 
     * @var \Metagist\Api\Validation\SchemaResolver
     */
    private $resolver;
    
    /**
     * Test setup.
     */
    public function setUp()
    {
        parent::setUp();
        $pushInfo = __DIR__ . '/../../../../../services/api.pushInfo.schema.json';
        
        $this->resolver = new SchemaResolver(
            array(
                'basepath' => __DIR__,
                'mapping' => array(
                    'pushInfo' => $pushInfo,
                    '404' => 'someSchema',
                    'disabled' => null,
                )
            )
        );
    }
    
    /**
     * Ensures the operation name is used to resolve the schema to use.
     */
    public function testGetSchemaForCommand()
    {
        $mock = $this->getMockBuilder("\Guzzle\Service\Command\OperationCommand")
            ->disableOriginalConstructor()
            ->getMock();
        $operation = $this->getMockBuilder("\Guzzle\Service\Description\Operation")
            ->disableOriginalConstructor()
            ->getMock();
        $operation->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('pushInfo'));
        $mock->expects($this->once())
            ->method('getOperation')
            ->will($this->returnValue($operation));
        
        $this->resolver->getSchemaForCommand($mock);
    }
    
    /**
     * Ensures successful resolving.
     */
    public function testGetSchemaForOperationName()
    {
        $schema = $this->resolver->getSchemaForOperationName('pushInfo');
        $this->assertInternalType('object', $schema);
    }
    
    /**
     * Ensures successful resolving.
     */
    public function testGetSchemaForDisabledValidationIsNull()
    {
        $schema = $this->resolver->getSchemaForOperationName('disabled');
        $this->assertNull($schema);
    }
    
    /**
     * 
     */
    public function testGetSchemaForOperationNameUnregisteredException()
    {
         $this->setExpectedException("\Metagist\Api\Validation\Exception");
         $this->resolver->getSchemaForOperationName('unknown');
    }
    
    /**
     * 
     */
    public function testGetSchemaForOperationNameFileNotFoundException()
    {
         $this->setExpectedException("\Metagist\Api\Validation\Exception");
         $this->resolver->getSchemaForOperationName('404');
    }
}
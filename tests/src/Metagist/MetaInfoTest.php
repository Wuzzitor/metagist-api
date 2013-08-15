<?php
namespace Metagist;

require_once __DIR__ . '/bootstrap.php';

/**
 * Tests the metainfo class.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class MetainfoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * system under test
     * @var Metainfo 
     */
    private $metaInfo;
    
    /**
     * Test setup
     */
    public function setUp()
    {
        parent::setUp();
        $this->metaInfo = new Metainfo();
    }
    
    /**
     * Ensures the array factory method returns a metainfo object.
     */
    public function testFactoryMethod()
    {
        $info = Metainfo::fromArray(array());
        $this->assertInstanceOf('Metagist\Metainfo', $info);
    }
    
    /**
     * Ensures the value factory method returns a metainfo object.
     */
    public function testFromValueFactoryMethod()
    {
        $info = Metainfo::fromValue('grp', 'test123', '1.0.0');
        $this->assertInstanceOf('Metagist\Metainfo', $info);
        $this->assertEquals('grp', $info->getGroup());
        $this->assertEquals('test123', $info->getValue());
        $this->assertEquals('1.0.0', $info->getVersion());
    }
    
    /**
     * Tests the group getter.
     */
    public function testGetGroup()
    {
        $this->metaInfo = Metainfo::fromArray(array('group' => 'test'));
        $this->assertEquals('test', $this->metaInfo->getGroup());
    }
    
    /**
     * Tests the value getter.
     */
    public function testGetValue()
    {
        $this->metaInfo = Metainfo::fromArray(array('value' => 'test'));
        $this->assertEquals('test', $this->metaInfo->getValue());
    }
    
    /**
     * Tests the version getter.
     */
    public function testGetVersion()
    {
        $this->metaInfo->setVersion('abc');
        $this->assertEquals('abc', $this->metaInfo->getVersion());
    }
    
    /**
     * Tests the time getter.
     */
    public function testGetTimeUpdated()
    {
        $this->metaInfo = Metainfo::fromArray(array('time_updated' => new \DateTime()));
        $this->assertInstanceOf("\DateTime", $this->metaInfo->getTimeUpdated());
    }
}
<?php
/**
 * MetaInfointerface.php
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
namespace Metagist;

use Metagist\PackageInterface;

/**
 * Interface for metainfos.
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface MetainfoInterface
{
    /**
     * Set the related package.
     * 
     * @param \Metagist\PackageInterface $package
     */
    public function setPackage(PackageInterface $package);
    
    /**
     * Returns the related package.
     * 
     * @return \Metagist\PackageInterface|null
     */
    public function getPackage();
    
    /**
     * Set the group name.
     * 
     * @param string
     */
    public function setGroup($group);
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    public function getGroup();
    
    /**
     * Set the value.
     * 
     * @param string|int|float|boolean $value
     */
    public function setValue($value);
    
    /**
     * Returns the value.
     * 
     * @return string|int|float|boolean
     */
    public function getValue();
    
    /**
     * Returns the associated version.
     * 
     * @return string|null
     */
    public function getVersion();
    
    /**
     * Set the version string.
     * 
     * @param string $version
     */
    public function setVersion($version);
    
    /**
     * Returns the time of the last update
     * 
     * @return \DateTime
     */
    public function getTimeUpdated();
}
<?php
/**
 * MetaInfointerface.php
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
namespace Metagist;

/**
 * Interface for metainfos.
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface MetainfoInterface
{
    /**
     * Returns the related package.
     * 
     * @return \Metagist\PackageInterface|null
     */
    public function getPackage();
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    public function getGroup();
    
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
     * Returns the time of the last update
     * 
     * @return \DateTime
     */
    public function getTimeUpdated();
}
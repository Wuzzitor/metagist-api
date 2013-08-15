<?php
/**
 * PackageInterface.php
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
namespace Metagist;

/**
 * Interface for packages.
 * 
 * @package metagist-api
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface PackageInterface
{
    /**
     * Returns the identifier of the package.
     * 
     * @return string
     */
    public function getIdentifier();
    
    /**
     * Returns the author/owner part of the package identifier.
     * 
     * @return boolean
     */
    public function getAuthor();
    
    /**
     * Returns the name part of the package identifier.
     * 
     * @return string|false
     */
    public function getName();
    
    /**
     * Get the description.
     * 
     * @return string
     */
    public function getDescription();

    /**
     * Returns all known versions.
     * 
     * @return array
     */
    public function getVersions();
    
    /**
     * Returns the type of the package.
     * 
     * @return string
     */
    public function getType();
    
    /**
     * Returns the associated metainfos.
     * 
     * @param string $group
     * @return \Doctrine\Common\Collections\Collection|null
     */
    public function getMetainfos($group = null);
    
    /**
     * Returns the time of the last update
     * 
     * @return \DateTime
     */
    public function getTimeUpdated();
}
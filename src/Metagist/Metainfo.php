<?php
/**
 * Metainfo.php
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @package metagist-api
 */
namespace Metagist;

/**
 * Metainfo value object.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @package metagist-api
 */
class Metainfo implements MetainfoInterface
{
    /**
     * identifier for the repo
     * 
     * @var string
     */
    const REPOSITORY = 'repository';
    
    /**
     * identifier for the package homepage
     * 
     * @var string
     */
    const HOMEPAGE = 'homepage';
    
    /**
     * identifier for number of maintainers
     * 
     * @var string
     */
    const MAINTAINERS = 'maintainers';
    
    /**
     * identifier for number of dependencies
     * 
     * @var string
     */
    const REQUIRES = 'requires';
    
    /**
     * identifier for number of dependencies for development
     * 
     * @var string
     */
    const REQUIRES_DEV = 'requires.dev';
    
    /**
     * identifier for license type
     * 
     * @var string
     */
    const LICENSE = 'license';
    
    /**
     * Identifier for number of github stargazers.
     * 
     * @var string
     */
    const STARGAZERS = 'stargazers';
    
    /**
     * Identifier for number of open issues
     * 
     * @var string
     */
    const OPEN_ISSUES = 'issues.open';
    
    /**
     * Identifier for number of closed issues
     * 
     * @var string
     */
    const CLOSED_ISSUES = 'issues.closed';
    
    /**
     * Number of project contributors (based on repo info).
     * 
     * @var string
     */
    const CONTRIBUTORS = 'contributors';
    
    /**
     * Number of commits (repo).
     * 
     * @var string
     */
    const COMMITS = 'commits';
    
    /**
     * The time of the last update.
     * 
     * @var string
     */
    private $time_updated;
    
    /**
     * group name
     * @var string
     */
    private $group;
    
    /**
     * Package 
     * @var Package 
     */
    private $package;
    
    /**
     * Version info
     * @var string 
     */
    private $version;
    
    /**
     * Content of the information.
     * @var string 
     */
    private $value;
    
    /**
     * Factory method.
     * 
     * @param array $data
     * @return Metainfo
     */
    public static function fromArray(array $data)
    {
        $info = new self();
        foreach ($data as $key => $value) {
            if (!property_exists($info, $key)) {
                continue;
            }
            $info->$key = $value;
        }
        
        return $info;
    }
    
    /**
     * Factory method to create metainfo based on values.
     * 
     * @param string $group
     * @param mixed  $value
     * @return Metainfo
     */
    public static function fromValue($group, $value, $version = null)
    {
        return self::fromArray(
            array(
                'group'    => $group,
                'value'    => $value,
                'version'  => $version
            )
        );
    }

    /**
     * Set the related package.
     * 
     * @param PackageInterface $package
     */
    public function setPackage(PackageInterface $package)
    {
        $this->package = $package;
    }
    
    /**
     * Returns the related package.
     * 
     * @return Package|null
     */
    public function getPackage()
    {
        return $this->package;
    }
    
    /**
     * Returns the group name.
     * 
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }
    
    /**
     * Returns the value.
     * 
     * @return string|int
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Returns the associated version.
     * 
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * Set the version string.
     * 
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }
    
    /**
     * Returns the time of the last update
     * 
     * @return string|null
     */
    public function getTimeUpdated()
    {
        return $this->time_updated;
    }
}
<?php
namespace Metagist\Api;

use Guzzle\Service\Command\ResponseClassInterface;

/**
 * Server method "package" response class.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class PackageResponse implements ResponseClassInterface
{
    /**
     * package instance.
     * 
     * @var \Metagist\Package|null 
     */
    private $package;
    
    /**
     * Constructor.
     * 
     * @param \Metagist\Package $package
     */
    public function __construct(\Metagist\Package $package)
    {
        $this->package = $package;
    }
    
    /**
     * Factory method.
     * 
     * @param \Guzzle\Service\Command\OperationCommand $command
     * @return PackageResponse
     */
    public static function fromCommand(\Guzzle\Service\Command\OperationCommand $command)
    {
        $json       = $command->getResponse()->json();
        $identifier = $json['identifier'];
        $package    = new \Metagist\Package($identifier);
        $metainfos  = $json['metaInfos'];
        
        $collection = new \Doctrine\Common\Collections\ArrayCollection();
        foreach ($metainfos as $data) {
            $collection->add(\Metagist\Metainfo::fromArray($data));
        }
        $package->setMetainfos($collection);
        return new PackageResponse($package);
    }

    /**
     * Returns the package instance.
     * 
     * @return \Metagist\Package
     */
    public function getPackage()
    {
        return $this->package;
    }
}

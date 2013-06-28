<?php
namespace Metagist\Api;

/**
 * Interface for the Metagist worker.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface WorkerInterface
{
    /**
     * Call to scan a package and retrieve metainfos.
     * 
     * @param string $author
     * @param string $name
     * @return void (asynchronous)
     * @throws \Metagist\Api\Exception
     */
    public function scan($author, $name);
}
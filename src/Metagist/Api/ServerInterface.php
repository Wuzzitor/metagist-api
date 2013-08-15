<?php
namespace Metagist\Api;

/**
 * Interface for the Metagist server (metagist.org).
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
interface ServerInterface
{
    /**
     * Triggers the server procedure to return package info.
     * 
     * @param string $author
     * @param string $name
     * @return \Metagist\Package
     * @throws \Metagist\Api\Exception
     */
    public function package($author, $name);
    
    /**
     * Pushes metainfo to the server.
     * 
     * @param string             $author
     * @param string             $name
     * @param \Metagist\Metainfo $info = null
     * @return mixed
     * @throws \Metagist\Api\Exception
     */
    public function pushInfo($author, $name, \Metagist\Metainfo $info = null);
}
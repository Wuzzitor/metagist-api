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
     * @return mixed
     */
    public function package($author, $name);
    
    /**
     * Pushes metainfo to the server.
     * 
     * @param string             $author
     * @param string             $name
     * @param \Metagist\MetaInfo $info = null
     * @return mixed
     * @throws \Metagist\Api\Exception
     */
    public function pushInfo($author, $name, \Metagist\MetaInfo $info = null);
}
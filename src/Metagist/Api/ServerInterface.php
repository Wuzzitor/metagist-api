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
     * @param string $author
     * @param string $name
     * @param array  $data
     * @return mixed
     */
    public function pushInfo($author, $name, array $data = array());
}
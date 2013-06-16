<?php
namespace Metagist\Api;

use Guzzle\Service\Client;

/**
 * Http client for the metagist server.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class ServerClient extends Client implements ServerInterface
{
    /**
     * Triggers the server procedure to return package info.
     * 
     * @param string $author
     * @param string $name
     * @return array
     */
    public function package($author, $name)
    {
        $args = array(
            'author' => $author,
            'name'   => $name
        );
        /* @var $command Guzzle\Service\Command\CommandInterface */
        $command = $this->getCommand('package', $args);
        return $command->execute();
    }

    /**
     * Pushes metainfo to the server.
     * 
     * @param string $author
     * @param string $name
     * @param array  $data
     * @return mixed
     */
    public function pushInfo($author, $name, array $data = array())
    {
        $args = array(
            'author' => $author,
            'name'   => $name,
            'data'   => $data
        );
        /* @var $command Guzzle\Service\Command\CommandInterface */
        $command = $this->getCommand('pushInfo', $args);
        return $command->execute();
    }

}
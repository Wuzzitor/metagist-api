<?php
namespace Metagist\Api;

use Guzzle\Service\Client;

/**
 * Http client for a remote worker.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class WorkerClient extends Client implements WorkerInterface 
{
    /**
     * Triggers the remote procedure "scan".
     * 
     * @param string $author
     * @param string $name
     */
    public function scan($author, $name)
    {
        $args = array(
            'author' => $author,
            'name'   => $name
        );
        /* @var $command Guzzle\Service\Command\CommandInterface */
        $command = $this->getCommand('scan', $args);
        return $command->execute();
    }
}
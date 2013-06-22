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
     * @param string             $author
     * @param string             $name
     * @param \Metagist\MetaInfo $info = null
     * @return mixed
     */
    public function pushInfo($author, $name, \Metagist\MetaInfo $info = null)
    {
        $args = array(
            'author' => $author,
            'name'   => $name,
            'info'   => $info
        );
        
        /* @var $command \Guzzle\Service\Command\OperationCommand */
        $command    = $this->getCommand('pushInfo', $args);
        $serializer = $command->getRequestSerializer();
        if ($serializer instanceof \Guzzle\Service\Command\DefaultRequestSerializer) {
            $serializer->addVisitor('body', $this->getRequestVisitor());
        }
        return $command->execute();
    }

    /**
     * Returns the json encoder for requests.
     * 
     * @return \Metagist\Api\JsonRequestVisitor
     */
    protected function getRequestVisitor()
    {
        $serializer = \JMS\Serializer\SerializerBuilder::create()->build();
        return new JsonRequestVisitor($serializer);
    }
}
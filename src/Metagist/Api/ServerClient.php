<?php
namespace Metagist\Api;

use Metagist\MetainfoInterface;
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
     * @return \Metagist\Package
     */
    public function package($author, $name)
    {
        $args = array(
            'author' => $author,
            'name'   => $name
        );
        /* @var $command Guzzle\Service\Command\CommandInterface */
        $command  = $this->getCommand('package', $args);
        
        /* @var $response \Metagist\Api\PackageResponse */
        $response = $command->execute();
        return $response->getPackage();
    }

    /**
     * Pushes metainfo to the server.
     * 
     * @param string             $author
     * @param string             $name
     * @param \Metagist\Metainfo $info = null
     * @return mixed
     */
    public function pushInfo($author, $name, MetainfoInterface $info = null)
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
        try {
            return $command->execute();
        } catch (\Metagist\Api\Validation\Exception $exception) {
            return $exception;
        }
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
<?php
/**
 * Util.php
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @package metagist-api
 */
namespace Metagist;

/**
 * Metagist Utility class.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 * @package metagist-api
 */
class Util
{
    /**
     * Returns author + name from a package identifier string.
     * 
     * @param string $identifier
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function splitIdentifier($identifier)
    {
        if (!is_string($identifier)) {
            throw new \InvalidArgumentException('Identifier must be a string.');
        }
        
        return explode('/', $identifier);
    }
}
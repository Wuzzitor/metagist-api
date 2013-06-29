<?php
namespace Metagist\Api\Validation\Plugin;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Common\Event;
use Metagist\Api\Validation\SchemaResolver;

/**
 * Schema validator plugin for guzzle.
 * 
 * Uses the json schema validator from https://github.com/justinrainbow/json-schema.
 * The plugin can also be used as standalone validator for incoming requests.
 * 
 * @author Daniel Pozzi <bonndan76@googlemail.com>
 */
class SchemaValidator implements EventSubscriberInterface
{ 
    /**
     * schema resolver
     * 
     * @var \Metagist\Api\Validation\SchemaResolver 
     */
    private $resolver;

    /**
     * Inject a schema resolver.
     * 
     * @param \Metagist\Api\Validation\SchemaResolver $resolver
     */
    public function __construct(SchemaResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * The schema validator subscribes to the "request.sent" event.
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'command.after_send' => array('onCommandSent', 255)
        );
    }

    /**
     * Validates the response body against the schema.
     * 
     * @param \Guzzle\Common\Event $event
     * @throws \Metagist\Api\Validation\Exception
     */
    public function onCommandSent(Event $event)
    {
        /* @var $command \Guzzle\Service\Command\OperationCommand */
        $command = $event['command'];
        $schema = $this->resolver->getSchemaForCommand($command);
        $response = $command->getResponse();
        //response->json() returns arrays, do not use
        $data = json_decode((string)$response->getBody());
        $this->validate($data, $schema);
    }

    /**
     * Validation of an incoming request.
     * 
     * Only requests containing an entity body can be validated.
     * 
     * @param EntityEnclosingRequest $request
     * @param string $operationName
     * @throws \Metagist\Api\Validation\Exception
     * @see \Guzzle\Http\Message\RequestFactory::fromMessage()
     */
    public function validateRequest(EntityEnclosingRequest $request, $operationName)
    {
        $json    = (string)$request->getBody();
        $schema  = $this->resolver->getSchemaForOperationName($operationName);
        $this->validate(json_decode($json), $schema);
    }

    /**
     * Validates the payload.
     * 
     * @param object $data
     * @param object $schema
     * @throws \Metagist\Api\Validation\Exception
     */
    protected function validate($data, $schema)
    {
        $validator = new \JsonSchema\Validator();
        $validator->check($data, $schema);

        if (!$validator->isValid()) {
            $errors = $this->validatorErrorsToString($validator->getErrors());
            throw new \Metagist\Api\Validation\Exception(
                'Data does not validate against ' . $schema->name . ': ' . $errors
            );
        }
    }

    /**
     * Concatenates the error messages.
     * 
     * @param array $errors
     * @return string
     */
    protected function validatorErrorsToString(array $errors)
    {
        $buffer = '';
        
        foreach ($errors as $data) {
            foreach ($data as $entry) {
                $buffer .= $entry . ', ';
            }
        }
        return $buffer;
    }
}
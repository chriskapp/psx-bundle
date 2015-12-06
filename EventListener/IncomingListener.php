<?php

namespace PSX\PSXBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use PSX\Data\Importer;
use PSX\Data\Schema\Parser\JsonSchema;
use PSX\Http\Message;
use PSX\PSXBundle\Context;
use PSX\PSXBundle\Configuration\Incoming;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class IncomingListener
{
    private $reader;
    private $importer;
    private $rootDir;

    public function __construct(Reader $reader, Importer $importer, $rootDir)
    {
        $this->reader   = $reader;
        $this->importer = $importer;
        $this->rootDir  = $rootDir;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        $object     = new \ReflectionObject($controller[0]);
        $method     = $object->getMethod($controller[1]);
        $annotation = $this->reader->getMethodAnnotation($method, 'PSX\PSXBundle\Configuration\Incoming');

        if ($annotation instanceof Incoming) {
            $file    = $this->rootDir . '/' . $annotation->getFile();
            $request = $event->getRequest();
            $message = new Message($request->server->getHeaders(), $request->getContent());
            $data    = $this->importer->import(JsonSchema::fromFile($file), $message);

            $request->attributes->set(Context::REQUEST_BODY, $data);
        }
    }
}

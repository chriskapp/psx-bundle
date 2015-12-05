<?php

namespace PSX\PSXBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use PSX\Data\Schema\Assimilator;
use PSX\Data\Schema\Parser\JsonSchema;
use PSX\Data\WriterFactory;
use PSX\Http\Message;
use PSX\PSXBundle\Context;
use PSX\PSXBundle\Configuration\Outgoing;
use PSX\PSXBundle\DataResponse;
use RuntimeException;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\Request;

class OutgoingListener
{
    private $reader;
    private $assimilator;
    private $writerFactory;
    private $rootDir;

    public function __construct(Reader $reader, Assimilator $assimilator, WriterFactory $writerFactory, $rootDir)
    {
        $this->reader        = $reader;
        $this->assimilator   = $assimilator;
        $this->writerFactory = $writerFactory;
        $this->rootDir       = $rootDir;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $controller = $event->getRequest()->attributes->get('_controller');
        if (empty($controller)) {
            return;
        }

        if (strpos($controller, '::') !== false) {
            $controller = explode('::', $controller, 2);
            $class      = new \ReflectionClass($controller[0]);
            $method     = $class->getMethod($controller[1]);
            $annotation = $this->reader->getMethodAnnotation($method, 'PSX\PSXBundle\Configuration\Outgoing');

            $response = $event->getResponse();
            $writer   = $this->getWriter($event->getRequest());

            if ($response instanceof DataResponse) {
                if ($annotation instanceof Outgoing) {
                    $file   = $this->rootDir . '/' . $annotation->getFile();
                    $data   = $this->assimilator->assimilate(JsonSchema::fromFile($file), $response->getData());

                    $response->headers->set('Content-Type', $writer->getContentType());
                    $response->setContent($writer->write($data));
                } else {
                    $response->headers->set('Content-Type', $writer->getContentType());
                    $response->setContent($writer->write($response->getData()));
                }
            } else {
                throw new RuntimeException('Can only transform responses of PSX\PSXBundle\DataResponse');
            }
        }
    }

    protected function getWriter(Request $request)
    {
        $attribute = $request->attributes->get(Context::RESPONSE_FORMAT);
        $query     = $request->query->get('format');
        if (!empty($attribute)) {
            return $this->writerFactory->getWriterByFormat($attribute);
        } elseif (!empty($query)) {
            return $this->writerFactory->getWriterByFormat($query);
        } else {
            return $this->writerFactory->getWriterByContentType(implode(', ', $request->getAcceptableContentTypes()));
        }
    }
}


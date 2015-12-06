<?php

namespace PSX\PSXBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use PSX\Data\Schema\Assimilator;
use PSX\Data\Schema\Parser\JsonSchema;
use PSX\Data\Record;
use PSX\Data\RecordInterface;
use PSX\Data\WriterFactory;
use PSX\Exception\ConverterInterface;
use PSX\Http\Message;
use PSX\Http\StatusCodeException;
use PSX\PSXBundle\Context;
use PSX\PSXBundle\Configuration\Outgoing;
use PSX\PSXBundle\DataResponse;
use RuntimeException;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OutgoingListener
{
    private $reader;
    private $assimilator;
    private $writerFactory;
    private $converter;
    private $rootDir;

    public function __construct(Reader $reader, Assimilator $assimilator, WriterFactory $writerFactory, ConverterInterface $converter, $rootDir)
    {
        $this->reader        = $reader;
        $this->assimilator   = $assimilator;
        $this->writerFactory = $writerFactory;
        $this->converter     = $converter;
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
                    $file = $this->rootDir . '/' . $annotation->getFile();
                    $data = $this->assimilator->assimilate(JsonSchema::fromFile($file), $response->getData());

                    $response->headers->set('Content-Type', $writer->getContentType());
                    $response->setContent($writer->write($data));
                } else {
                    $data = $this->normalizeData($response->getData());

                    $response->headers->set('Content-Type', $writer->getContentType());
                    $response->setContent($writer->write($data));
                }
            } else {
                throw new RuntimeException('Can only transform responses of PSX\PSXBundle\DataResponse');
            }
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception  = $event->getException();
        $statusCode = 500;

        if ($exception instanceof StatusCodeException) {
            $statusCode = $exception->getStatusCode();
        }

        $data   = $this->converter->convert($exception);
        $writer = $this->getWriter($event->getRequest());

        $content  = $writer->write($data);
        $headers  = ['Content-Type', $writer->getContentType()];
        $response = new Response($content, $statusCode, $headers);

        $event->setResponse($response);
    }

    protected function getWriter(Request $request)
    {
        $attribute = $request->attributes->get(Context::RESPONSE_FORMAT);
        $query     = $request->query->get('format');
        if (!empty($attribute)) {
            $writer = $this->writerFactory->getWriterByFormat($attribute);
        } elseif (!empty($query)) {
            $writer = $this->writerFactory->getWriterByFormat($query);
        } else {
            $writer = $this->writerFactory->getWriterByContentType(implode(', ', $request->getAcceptableContentTypes()));
        }

        if ($writer === null) {
            $writer = $this->writerFactory->getDefaultWriter();
        }

        return $writer;
    }

    protected function normalizeData($data)
    {
        if (is_array($data)) {
            return new Record('record', $data);
        } elseif ($data instanceof \stdClass) {
            return new Record('record', (array) $data);
        } elseif ($data instanceof RecordInterface) {
            return $record;
        } else {
            throw new RuntimeException('Invalid response data must be either an array, stdClass or PSX\Data\RecordInterface');
        }
    }
}



PSX Bundle
========================

This Bundle provides basic PSX functionalities to Symfony. The bundle provides
two annotations `Incoming` and `Outgoing` which give the option to specify a
JSONSchema for the incoming or outgoing data. The bundle registers a 
`kernel.controller` and `kernel.response` listener to handle the validation and 
transformation. More information about PSX at http://phpsx.org.

Usage
-----

In the following a sample controller to showcase the usage

```php
<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use PSX\PSXBundle\Configuration\Incoming;
use PSX\PSXBundle\Configuration\Outgoing;
use PSX\PSXBundle\DataResponse;

class DefaultController extends Controller
{
    /**
     * @Method({"GET"})
     * @Route("/", name="index")
     * @Outgoing("schema/incoming.json")
     */
    public function indexAction(Request $request)
    {
        return new DataResponse([
            'firstName' => 'bar',
            'lastName' => 'bar'
        ]);
    }

    /**
     * @Method({"POST"})
     * @Route("/", name="new")
     * @Incoming("schema/outgoing.json")
     */
    public function newAction(Request $request)
    {
        $data = $request->attributes->get(Context::REQUEST_BODY);

        // @TODO work with the data
        // $data->getFirstName();

        return new DataResponse([
            'success' => true,
            'message' => 'Success!'
        ]);
    }
}
```


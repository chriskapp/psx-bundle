<?php
/*
 * This file is part of the PSX Bundle
 *
 * Copyright (c) Christoph Kappestein <k42b3.x@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PSX\PSXBundle;

use Symfony\Component\HttpFoundation\Response;

class DataResponse extends Response
{
    protected $data;

    public function __construct($data = null, $status = 200, $headers = array())
    {
        parent::__construct('', $status, $headers);

        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}

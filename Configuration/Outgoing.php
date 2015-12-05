<?php
/*
 * This file is part of the PSX Bundle
 *
 * Copyright (c) Christoph Kappestein <k42b3.x@gmail.com>
 *
 * For the full license information, please view the LICENSE file that was 
 * distributed with this source code.
 */

namespace PSX\PSXBundle\Configuration;

/**
 * Outgoing
 *
 * @Annotation
 */
class Outgoing
{
    protected $file;

    public function __construct(array $config)
    {
        $this->file = isset($config['value']) ? $config['value'] : null;
    }

    public function getFile()
    {
        return $this->file;
    }
}

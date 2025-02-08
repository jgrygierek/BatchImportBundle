<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Event;

class RecordImportedSuccessfullyEvent
{
    public function __construct(readonly public string $class, readonly public string $id)
    {
    }
}

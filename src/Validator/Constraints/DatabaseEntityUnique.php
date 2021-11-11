<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DatabaseEntityUnique extends Constraint
{
    public string $message = 'validation.entity.unique';
    public string $entityClassName;
    public array $fields;

    public function getDefaultOption(): string
    {
        return 'entityClassName';
    }

    public function getRequiredOptions(): array
    {
        return ['entityClassName', 'fields'];
    }
}

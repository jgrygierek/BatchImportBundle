<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Form\Type;

use JG\BatchEntityImportBundle\Form\Type\FileImportType;
use JG\BatchEntityImportBundle\Model\FileImport;
use Symfony\Component\Form\Test\TypeTestCase;

class FileImportTypeTest extends TypeTestCase
{
    public function testForm(): void
    {
        $formData = [];
        $fileImport = new FileImport();
        $form = $this->factory->create(FileImportType::class, $fileImport);

        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertSame($fileImport, $form->getData());
    }
}

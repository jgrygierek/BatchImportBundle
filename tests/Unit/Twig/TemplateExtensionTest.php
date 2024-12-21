<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Twig;

use JG\BatchEntityImportBundle\Twig\TemplateExtension;
use PHPUnit\Framework\TestCase;

class TemplateExtensionTest extends TestCase
{
    public function testValidTemplates(): void
    {
        $extension = new TemplateExtension([
            'select_file' => 'abcd',
            'edit_matrix' => 'xyz',
            'layout' => '12345',
        ]);

        self::assertSame('abcd', $extension->getTemplate('select_file'));
        self::assertSame('xyz', $extension->getTemplate('edit_matrix'));
        self::assertSame('12345', $extension->getTemplate('layout'));
    }

    public function testWrongTemplateException(): void
    {
        $this->expectExceptionMessage('Template wrong_template not found.');

        $extension = new TemplateExtension([]);
        $extension->getTemplate('wrong_template');
    }
}

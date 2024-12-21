<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\DependencyInjection;

use JG\BatchEntityImportBundle\Controller\ImportConfigurationAutoInjectInterface;
use JG\BatchEntityImportBundle\DependencyInjection\BatchEntityImportExtension;
use JG\BatchEntityImportBundle\Model\Configuration\ImportConfigurationInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class BatchEntityImportExtensionTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->load();
    }

    public function testParameters(): void
    {
        $this->load();

        $this->assertContainerBuilderHasParameter('batch_entity_import.templates.select_file', '@BatchEntityImport/select_file.html.twig');
        $this->assertContainerBuilderHasParameter('batch_entity_import.templates.edit_matrix', '@BatchEntityImport/edit_matrix.html.twig');
        $this->assertContainerBuilderHasParameter('batch_entity_import.templates.layout', '@BatchEntityImport/layout.html.twig');
    }

    public function testImportConfigurationHasTag(): void
    {
        $autoConfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ImportConfigurationInterface::class, $autoConfiguredInstances);

        $instance = $autoConfiguredInstances[ImportConfigurationInterface::class];
        self::assertTrue($instance->hasTag('batch_entity_import.configuration'));
    }

    public function testControllerHasTag(): void
    {
        $autoConfiguredInstances = $this->container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ImportConfigurationAutoInjectInterface::class, $autoConfiguredInstances);

        $instance = $autoConfiguredInstances[ImportConfigurationAutoInjectInterface::class];
        self::assertTrue($instance->hasTag('batch_entity_import.controller'));
    }

    protected function getContainerExtensions(): array
    {
        return [new BatchEntityImportExtension()];
    }
}

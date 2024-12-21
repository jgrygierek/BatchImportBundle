<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Service;

use Generator;
use JG\BatchEntityImportBundle\Service\PropertyExistenceChecker;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TestEntity;
use PHPUnit\Framework\TestCase;

class PropertyExistenceCheckerTest extends TestCase
{
    private PropertyExistenceChecker $checkerEntity;

    protected function setUp(): void
    {
        $this->checkerEntity = new PropertyExistenceChecker(TestEntity::class);
    }

    /**
     * @dataProvider dataProviderValidProperty
     */
    public function testEntityHasProperty(string $property): void
    {
        self::assertTrue($this->checkerEntity->propertyExists($property));
    }

    public static function dataProviderValidProperty(): Generator
    {
        yield ['test_private_property'];
        yield ['testPrivateProperty'];
        yield ['test_public_property'];
        yield ['testPublicProperty'];
    }

    /**
     * @dataProvider dataProviderWrongProperty
     */
    public function testEntityWithoutProperty(string $property): void
    {
        self::assertFalse($this->checkerEntity->propertyExists($property));
    }

    public static function dataProviderWrongProperty(): Generator
    {
        yield ['wrong_property'];
        yield ['wrongProperty'];
        yield ['test_private_property_no_setter'];
        yield ['testPrivatePropertyNoSetter'];
    }
}

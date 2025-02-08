<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Utils;

use Generator;
use JG\BatchEntityImportBundle\Utils\ColumnNameHelper;
use PHPUnit\Framework\TestCase;

class ColumnNameHelperTest extends TestCase
{
    /**
     * @dataProvider pascalCaseDataProvider
     */
    public function testToPascalCaseSuccess(string $underscoreString, string $expected): void
    {
        self::assertEquals($expected, ColumnNameHelper::toPascalCase($underscoreString));
    }

    public static function pascalCaseDataProvider(): Generator
    {
        yield ['aa_bb_cc', 'AaBbCc'];
        yield ['aa_bB_cc', 'AaBBCc'];
        yield ['aa bb cc', 'AaBbCc'];
        yield ['aa-bb-cc', 'AaBbCc'];
        yield ['AaBbCc', 'AaBbCc'];
        yield ['', ''];
    }

    /**
     * @dataProvider camelCaseDataProvider
     */
    public function testToCamelCaseSuccess(string $underscoreString, string $expected): void
    {
        self::assertEquals($expected, ColumnNameHelper::toCamelCase($underscoreString));
    }

    public static function camelCaseDataProvider(): Generator
    {
        yield ['aa_bb_cc', 'aaBbCc'];
        yield ['aa_bB_cc', 'aaBBCc'];
        yield ['aa bb cc', 'aaBbCc'];
        yield ['aa-bb-cc', 'aaBbCc'];
        yield ['AaBbCc', 'aaBbCc'];
        yield ['', ''];
    }

    /**
     * @dataProvider setterDataProvider
     */
    public function testCovnertToSetter(string $underscoreString, string $expected): void
    {
        self::assertEquals($expected, ColumnNameHelper::getSetterName($underscoreString));
    }

    public static function setterDataProvider(): Generator
    {
        yield ['aa_bb_cc', 'setAaBbCc'];
        yield ['aa_bB_cc', 'setAaBBCc'];
        yield ['aa bb cc', 'setAaBbCc'];
        yield ['aa-bb-cc', 'setAaBbCc'];
        yield ['AaBbCc', 'setAaBbCc'];
        yield ['', 'set'];
    }
}

<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Model\Matrix;

use Generator;
use JG\BatchEntityImportBundle\Model\Matrix\Matrix;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use JG\BatchEntityImportBundle\Tests\AbstractValidationTestCase;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TestEntity;

class MatrixTest extends AbstractValidationTestCase
{
    /**
     * @dataProvider getValidDataProvider
     */
    public function testValidMatrix(array $header, array $recordsData): void
    {
        $matrix = new Matrix($header, $recordsData);
        self::assertEmpty($this->getErrors($matrix));
    }

    public static function getValidDataProvider(): Generator
    {
        yield [['column_name'], [['column_name' => '']]];
        yield [['column_name'], [['column_name' => '', ' ' => '']]];
        yield [['', 'column_name'], [['column_name' => '']]];
        yield [['column-name'], [['column-name' => '']]];
        yield [['column name'], [['column name' => '']]];
    }

    /**
     * @dataProvider getInvalidDataProvider
     */
    public function testInvalidMatrix(array $header, array $recordsData): void
    {
        $matrix = new Matrix($header, $recordsData);
        self::assertNotEmpty($this->getErrors($matrix));
    }

    public static function getInvalidDataProvider(): Generator
    {
        yield [[], [['column_name' => '']]];
        yield [[' '], [['column_name' => '']]];
        yield [['wrong@column!name'], [['column_name' => '']]];
        yield [['column_name'], []];
        yield [['column_name'], [[' ' => '']]];
    }

    public function testRemoveColumnsWithEmptyHeader(): void
    {
        $header = ['column_name', '', 'column_name2', null, ' '];
        $recordsData = [
            ['column_name' => '', '' => '', 'column_name2' => '', ' ' => ''],
        ];

        $matrix = new Matrix($header, $recordsData);

        $expectedHeader = ['column_name', 'column_name2'];
        self::assertSame($expectedHeader, $matrix->getHeader());

        $expectedRecords = [new MatrixRecord(['column_name' => '', 'column_name2' => ''])];
        self::assertEquals($expectedRecords, $matrix->getRecords());
    }

    public function testHeaderInfoForNonTranslatableEntity(): void
    {
        $expected = [
            'unknown_column_name' => false,
            'test_public_property' => true,
            'test_private_property' => true,
            'test_private_property_no_setter' => false,
            'test_private_property:en' => false,
        ];

        $matrix = new Matrix(array_keys($expected));

        self::assertSame($expected, $matrix->getHeaderInfo(TestEntity::class));
    }
}

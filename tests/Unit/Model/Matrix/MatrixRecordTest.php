<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Model\Matrix;

use Generator;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use JG\BatchEntityImportBundle\Tests\AbstractValidationTestCase;
use stdClass;

class MatrixRecordTest extends AbstractValidationTestCase
{
    /**
     * @dataProvider getValidDataProvider
     */
    public function testData(array $recordData, array $expectedRecordData): void
    {
        $matrixRecord = new MatrixRecord($recordData);
        self::assertSame($expectedRecordData, $matrixRecord->getData());
    }

    public static function getValidDataProvider(): Generator
    {
        $class = new stdClass();

        yield [
            [
                'column_name' => '',
                '' => ' ',
                'column name2' => '',
                'column-name3' => '',
                'column-name4' => true,
                'column-name5' => $class,
                'column-name6' => 12,
                '1' => '',
            ],
            [
                'column_name' => '',
                'column_name2' => '',
                'column-name3' => '',
                'column-name4' => true,
                'column-name5' => $class,
                'column-name6' => 12,
                '1' => '',
            ],
        ];
        yield [['' => '', ' ' => ''], []];
    }
}

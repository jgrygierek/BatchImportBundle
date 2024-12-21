<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Model\Matrix;

use Generator;
use JG\BatchEntityImportBundle\Enums\CsvDelimiterEnum;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixFactory;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MatrixFactoryTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     *
     * @throws SpreadsheetException
     * @throws Exception
     */
    public function testCreateFromUploadedFileSuccess(string $fileExtension, CsvDelimiterEnum $delimiter = CsvDelimiterEnum::COMMA): void
    {
        foreach ($this->contentProvider() as $data) {
            $file = $this->createFile($fileExtension, $delimiter, \array_merge([$data['header']], $data['records']));
            $matrix = MatrixFactory::createFromUploadedFile($file);

            self::assertCount(count($data['expectedHeader']), $matrix->getHeader());
            self::assertCount(count($data['expectedRecords']), $matrix->getRecords());

            foreach ($data['expectedRecords'] as $index => $record) {
                self::assertSame($record, \array_values($matrix->getRecords()[$index]->getData()));
            }

            unlink($file->getPathname());
        }
    }

    public static function dataProvider(): Generator
    {
        yield ['csv', CsvDelimiterEnum::COMMA];
        yield ['csv', CsvDelimiterEnum::SEMICOLON];
        yield ['xls'];
        yield ['xlsx'];
        yield ['ods'];
        yield ['CSV', CsvDelimiterEnum::COMMA];
        yield ['CSV', CsvDelimiterEnum::SEMICOLON];
        yield ['XLS'];
        yield ['XLSX'];
        yield ['ODS'];
    }

    public function contentProvider(): Generator
    {
        yield [
            'header' => ['header1', 'header2', 'header3'],
            'expectedHeader' => ['header1', 'header2', 'header3'],
            'records' => [['aaaa', 'bbbb', 'cccc'], ['xxxx', 'yyyy', 'zzzz']],
            'expectedRecords' => [['aaaa', 'bbbb', 'cccc'], ['xxxx', 'yyyy', 'zzzz']],
        ];
        yield [
            'header' => ['header1', 'header2', 'header3'],
            'expectedHeader' => ['header1', 'header2', 'header3'],
            'records' => [],
            'expectedRecords' => [],
        ];
        yield [
            'header' => ['header1:en'],
            'expectedHeader' => ['header1:en'],
            'records' => [['aaaa']],
            'expectedRecords' => [['aaaa']],
        ];
        yield [
            'header' => [null],
            'expectedHeader' => [],
            'records' => [['abcd']],
            'expectedRecords' => [],
        ];
        yield [
            'header' => [],
            'expectedHeader' => [],
            'records' => [],
            'expectedRecords' => [],
        ];
    }

    /**
     * @throws Exception
     * @throws SpreadsheetException
     */
    public function testCreateFromUploadedFileWrongExtension(): void
    {
        $this->expectExceptionMessage('Reader for extension Txt is not supported by PhpOffice.');

        $file = new UploadedFile(__DIR__ . '/../../../Fixtures/Resources/test_wrong_extension.txt', 'test_wrong_extension.txt');
        MatrixFactory::createFromUploadedFile($file);
    }

    /**
     * @dataProvider postContentProvider
     */
    public function testCreateFromPostDataSuccess(array $data, bool $isHeader, int $recordsNumber): void
    {
        $matrix = MatrixFactory::createFromPostData($data);
        self::assertEquals($isHeader, !empty($matrix->getHeader()));
        self::assertCount($recordsNumber, $matrix->getRecords());
    }

    public static function postContentProvider(): Generator
    {
        yield [[['aaaa', 'bbbb', '123'], ['xxxx', 'yyyy', '456']], true, 2];
        yield [[['aaaa', 'bbbb', '123']], true, 1];
        yield [[[null], ['abcd']], false, 0];
        yield [[], false, 0];
    }

    /**
     * @throws SpreadsheetException
     * @throws WriterException
     */
    private function createFile(string $fileExtension, CsvDelimiterEnum $delimiter, array $data = []): UploadedFile
    {
        $fileExtension = strtolower($fileExtension);
        $filename = 'file.' . $fileExtension;

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($data);

        $writer = IOFactory::createWriter($spreadsheet, ucfirst($fileExtension));
        if ($writer instanceof Csv) {
            $writer->setDelimiter($delimiter->value);
        }
        $writer->save($filename);

        return new UploadedFile($filename, $filename);
    }
}

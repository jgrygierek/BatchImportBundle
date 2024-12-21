<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Model;

use Generator;
use JG\BatchEntityImportBundle\Model\FileImport;
use JG\BatchEntityImportBundle\Tests\AbstractValidationTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileImportTest extends AbstractValidationTestCase
{
    private ?string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = tempnam(sys_get_temp_dir(), 'upl');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        $this->path = null;
    }

    /**
     * @dataProvider validExtensionsProvider
     */
    public function testValidFile(string $extension, array $allowedExtensions): void
    {
        $fileImport = new FileImport($allowedExtensions);
        $this->setUploadedFile($fileImport, $extension);

        self::assertEmpty($this->getErrors($fileImport));
    }

    public static function validExtensionsProvider(): Generator
    {
        yield ['csv', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['xls', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['xlsx', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['ods', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['CSV', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['XLS', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['XLSX', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['ODS', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['csv', ['CSV', 'XLS', 'XLSX', 'ODS']];
        yield ['xls', ['CSV', 'XLS', 'XLSX', 'ODS']];
        yield ['xlsx', ['CSV', 'XLS', 'XLSX', 'ODS']];
        yield ['ods', ['CSV', 'XLS', 'XLSX', 'ODS']];
    }

    public function testEmptyFileError(): void
    {
        $fileImport = new FileImport(['csv', 'xls', 'xlsx', 'ods']);
        $this->setUploadedFile($fileImport, 'csv', false);

        self::assertNotEmpty($this->getErrors($fileImport));
    }

    /**
     * @dataProvider invalidExtensionsProvider
     */
    public function testInvalidExtensionError(string $extension, array $allowedExtensions): void
    {
        $fileImport = new FileImport($allowedExtensions);
        $this->setUploadedFile($fileImport, $extension);

        self::assertNotEmpty($this->getErrors($fileImport));
    }

    public static function invalidExtensionsProvider(): Generator
    {
        yield ['jpg', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['pdf', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['txt', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['', ['csv', 'xls', 'xlsx', 'ods']];
        yield ['csv', []];
    }

    public function testEmptyContentError(): void
    {
        $fileImport = new FileImport(['csv', 'xls', 'xlsx', 'ods']);
        $fileImport->setFile($this->createUploadedFile('csv', false));

        self::assertNotEmpty($this->getErrors($fileImport));
    }

    private function setUploadedFile(FileImport $fileImport, string $fileExtension, bool $withContent = true): void
    {
        $fileImport->setFile($this->createUploadedFile($fileExtension, $withContent));
    }

    private function createUploadedFile(string $fileExtension, bool $withContent = true): UploadedFile
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->path, $withContent ? 'test_content' : '');

        return new UploadedFile(
            $this->path,
            'test_file' . ($fileExtension ? '.' . $fileExtension : ''),
            null,
            null,
            true,
        );
    }
}

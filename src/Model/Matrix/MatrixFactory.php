<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Model\Matrix;

use InvalidArgumentException;
use JG\BatchEntityImportBundle\Service\CsvDelimiterDetector;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MatrixFactory
{
    /**
     * @throws InvalidArgumentException
     */
    public static function createFromUploadedFile(UploadedFile $file): Matrix
    {
        $reader = self::getReader($file);
        $spreadsheet = $reader->load($file->getPathname());

        $data = $spreadsheet->getActiveSheet()->toArray();
        $header = array_shift($data);
        self::addKeysToRows($header, $data);

        return new Matrix($header, $data);
    }

    public static function createFromPostData(array $data): Matrix
    {
        return $data ? new Matrix(array_keys($data[0]), $data) : new Matrix();
    }

    private static function addKeysToRows(array $header, array &$data): void
    {
        array_walk(
            $data,
            static function (array &$row) use ($header): void {
                $row = array_combine($header, $row);
            },
        );
    }

    private static function getReader(UploadedFile $file): BaseReader
    {
        $extension = ucfirst(strtolower($file->getClientOriginalExtension()));
        $readerClass = 'PhpOffice\PhpSpreadsheet\Reader\\' . $extension;
        if (!class_exists($readerClass)) {
            throw new InvalidArgumentException("Reader for extension $extension is not supported by PhpOffice.");
        }

        $reader = new $readerClass();
        if ($reader instanceof Csv) {
            $detectedDelimiter = (new CsvDelimiterDetector())->detect($file->getContent());
            $reader->setDelimiter($detectedDelimiter->value);
        } elseif ($reader instanceof Xls || $reader instanceof Xlsx) {
            $reader->setIgnoreRowsWithNoCells(true);
        }

        return $reader;
    }
}

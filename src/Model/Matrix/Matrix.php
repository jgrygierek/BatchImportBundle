<?php

namespace JG\BatchEntityImportBundle\Model\Matrix;

use JG\BatchEntityImportBundle\Service\PropertyExistenceChecker;
use Symfony\Component\Validator\Constraints as Assert;

class Matrix
{
    /**
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\NotBlank(),
     *     @Assert\Type("string"),
     *     @Assert\Regex(pattern="/^([\w]+)(:[\w]+)?$/", message="validation.matrix.header.name")
     * })
     */
    private array $header;
    /**
     * @var array|MatrixRecord[]
     *
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\Type(MatrixRecord::class)
     * })
     */
    private array $records = [];

    public function __construct(array $header = [], array $recordsData = [])
    {
        $this->header = $this->clearHeader($header);

        foreach ($recordsData as $data) {
            $data = $this->clearRecordData($data);
            if ($data) {
                $this->records[] = new MatrixRecord($data);
            }
        }
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return array|MatrixRecord[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function getHeaderInfo(string $className): array
    {
        $info = [];
        $checker = new PropertyExistenceChecker(new $className());

        foreach ($this->header as $name) {
            $info[$name] = $checker->propertyExists($name);
        }

        return $info;
    }

    private function clearHeader(array $header): array
    {
        return array_values(
            array_filter($header, fn ($e) => !empty(trim($e)))
        );
    }

    private function clearRecordData(array $data): array
    {
        return array_filter($data, fn ($key) => !empty(trim($key)), ARRAY_FILTER_USE_KEY);
    }
}

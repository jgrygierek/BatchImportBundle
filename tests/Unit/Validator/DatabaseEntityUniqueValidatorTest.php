<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Validator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixFactory;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TestEntity;
use JG\BatchEntityImportBundle\Tests\KnpLabs\Fixtures\Entity\TranslatableEntity;
use JG\BatchEntityImportBundle\Validator\Constraints\DatabaseEntityUnique;
use JG\BatchEntityImportBundle\Validator\Constraints\DatabaseEntityUniqueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use TypeError;

class DatabaseEntityUniqueValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var MockObject|EntityManager
     */
    private MockObject $entityManagerMock;

    protected function createValidator(): DatabaseEntityUniqueValidator
    {
        $this->entityManagerMock = $this->createMock(EntityManager::class);

        return new DatabaseEntityUniqueValidator($this->entityManagerMock);
    }

    public function testNoDuplication(): void
    {
        $queryMock = $this->createMock(Query::class);
        $this->entityManagerMock->method('createQuery')->willReturn($queryMock);

        $constraint = new DatabaseEntityUnique(['fields' => ['field_1'], 'entityClassName' => TestEntity::class]);
        $this->validator->validate(
            MatrixFactory::createFromPostData([
                [
                    'field_1' => 1,
                    'field_2' => 100,
                ],
                [
                    'field_1' => 2,
                    'field_2' => 101,
                ],
            ]),
            $constraint,
        );
        $this->assertNoViolation();
    }

    public function testDuplications(): void
    {
        $queryMock = $this->createMock(Query::class);
        $queryMock->method('getArrayResult')->willReturn(['']);
        $this->entityManagerMock->method('createQuery')->willReturn($queryMock);

        $matrix = MatrixFactory::createFromPostData([
            [
                'field_1' => 0,
                'field_2' => 100,
            ],
            [
                'field_1' => 0,
                'field_2' => 100,
            ],
        ]);
        $constraint = new DatabaseEntityUnique(['fields' => ['field_1'], 'entityClassName' => TestEntity::class]);
        $this->validator->validate($matrix, $constraint);
        $violations = $this->context->getViolations();
        $this->assertCount(2, $violations);
        $this->assertSame($matrix->getRecords()[0], $violations->get(0)->getInvalidValue());
    }

    public function testInvalidFieldNameException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Option "fields" contains invalid data. Allowed fields: field_1, field_2'));
        $matrix = MatrixFactory::createFromPostData([
            [
                'field_1' => 0,
                'field_2' => 100,
            ],
        ]);
        $constraint = new DatabaseEntityUnique(['fields' => ['field_3'], 'entityClassName' => 'test']);
        $this->validator->validate($matrix, $constraint);
    }

    public function testMissingEntityClassNameOptionException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Option "entityClassName" should not be empty.'));
        new DatabaseEntityUnique(['fields' => ['abcd'], 'entityClassName' => '']);
    }

    public function testMissingFieldsOptionException(): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Option "fields" should not be empty.'));
        new DatabaseEntityUnique(['fields' => [], 'entityClassName' => 'abcd']);
    }

    public function testValidatedValueException(): void
    {
        $this->expectException(TypeError::class);
        $this->validator->validate('qwerty', new DatabaseEntityUnique(['fields' => ['abcd'], 'entityClassName' => TranslatableEntity::class]));
    }

    public function testValidatorConstraintException(): void
    {
        $constraint = new Blank();
        $this->expectExceptionObject(new UnexpectedTypeException($constraint, DatabaseEntityUnique::class));
        $this->validator->validate(MatrixFactory::createFromPostData([]), $constraint);
    }
}

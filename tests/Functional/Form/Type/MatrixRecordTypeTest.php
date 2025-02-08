<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Functional\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use JG\BatchEntityImportBundle\Form\Type\ArrayTextType;
use JG\BatchEntityImportBundle\Form\Type\MatrixRecordType;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use JG\BatchEntityImportBundle\Tests\Fixtures\Configuration\BaseConfiguration;
use JG\BatchEntityImportBundle\Tests\Fixtures\Configuration\FieldsTypeConfiguration;
use JG\BatchEntityImportBundle\Tests\Fixtures\Entity\TestEntity;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MatrixRecordTypeTest extends WebTestCase
{
    private ?FormFactory $factory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->factory = self::getContainer()->get('form.factory');
    }

    public function testValidFormWithBaseConfig(): void
    {
        $data = $this->getRecordData();
        $configuration = new BaseConfiguration($this->createMock(EntityManagerInterface::class), $this->createMock(EventDispatcherInterface::class));
        $matrixRecord = new MatrixRecord($data);

        $form = $this->factory->create(MatrixRecordType::class, $matrixRecord, ['configuration' => $configuration]);
        $form->submit($data);

        self::assertTrue($form->isSynchronized());

        self::assertSame(TestEntity::class, $form->get('entity')->getConfig()->getOption('class'));
        self::assertInstanceOf(TextType::class, $form->get('age')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(TextType::class, $form->get('description')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(TextType::class, $form->get('array_field')->getConfig()->getType()->getInnerType());
    }

    public function testValidFormWithFieldsConfig(): void
    {
        $data = $this->getRecordData();
        $configuration = new FieldsTypeConfiguration($this->createMock(EntityManagerInterface::class), $this->createMock(EventDispatcherInterface::class));
        $matrixRecord = new MatrixRecord($data);

        $form = $this->factory->create(MatrixRecordType::class, $matrixRecord, ['configuration' => $configuration]);
        $form->submit($data);

        self::assertTrue($form->isSynchronized());

        self::assertSame(TestEntity::class, $form->get('entity')->getConfig()->getOption('class'));
        self::assertInstanceOf(IntegerType::class, $form->get('age')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(TextType::class, $form->get('name')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(TextareaType::class, $form->get('description')->getConfig()->getType()->getInnerType());
        self::assertInstanceOf(ArrayTextType::class, $form->get('array_field')->getConfig()->getType()->getInnerType());
    }

    public function testInvalidFormWithoutConfiguration(): void
    {
        $this->expectException(MissingOptionsException::class);

        $matrixRecord = new MatrixRecord();
        $this->factory->create(MatrixRecordType::class, $matrixRecord);
    }

    private function getRecordData(): array
    {
        return [
            'age' => 12,
            'name' => 'John Doe',
            'description' => 'Lorem Ipsum',
            'array_field' => 'value1|value2|value3',
        ];
    }
}

<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\KnpLabs\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use JG\BatchEntityImportBundle\Form\Type\MatrixRecordType;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use JG\BatchEntityImportBundle\Tests\KnpLabs\Fixtures\Configuration\TranslatableEntityConfiguration;
use JG\BatchEntityImportBundle\Tests\KnpLabs\Fixtures\Entity\TranslatableEntity;
use JG\BatchEntityImportBundle\Tests\SkippedTestsTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactory;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MatrixRecordTypeTest extends WebTestCase
{
    use SkippedTestsTrait;

    private ?FormFactory $factory;

    protected function setUp(): void
    {
        $this->markKnpLabsTestAsSkipped();

        self::bootKernel();

        $this->factory = self::getContainer()->get('form.factory');
    }

    public function testValidFormWithTranslatableField(): void
    {
        $data = ['description:pl' => 'Lorem Ipsum'];
        $configuration = new TranslatableEntityConfiguration(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(EventDispatcherInterface::class),
        );
        $matrixRecord = new MatrixRecord($data);

        $form = $this->factory->create(MatrixRecordType::class, $matrixRecord, ['configuration' => $configuration]);
        $form->submit($data);

        self::assertTrue($form->isSynchronized());

        self::assertSame(TranslatableEntity::class, $form->get('entity')->getConfig()->getOption('class'));
        self::assertInstanceOf(TextType::class, $form->get('description:pl')->getConfig()->getType()->getInnerType());
    }
}

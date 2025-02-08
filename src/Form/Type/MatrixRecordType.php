<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use JG\BatchEntityImportBundle\Model\Configuration\ImportConfigurationInterface;
use JG\BatchEntityImportBundle\Model\Form\FormFieldDefinition;
use JG\BatchEntityImportBundle\Model\Matrix\MatrixRecord;
use JG\BatchEntityImportBundle\Utils\ColumnNameHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatrixRecordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ImportConfigurationInterface $configuration */
        $configuration = $options['configuration'];
        $fieldDefinitions = $configuration->getFieldsDefinitions();

        if ($configuration->allowOverrideEntity()) {
            $this->addEntityField($builder, $configuration->getEntityClassName(), $configuration->getEntityTranslationRelationName());
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($fieldDefinitions): void {
                $record = $event->getData();
                if ($record instanceof MatrixRecord) {
                    foreach ($record->getData() as $columnName => $value) {
                        $this->addField($fieldDefinitions, $columnName, $event);
                    }
                }
            },
        );
    }

    /**
     * @throws AccessException
     * @throws UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults(['data_class' => MatrixRecord::class])
            ->setRequired('configuration')
            ->addAllowedTypes('configuration', ImportConfigurationInterface::class);
    }

    private function addEntityField(FormBuilderInterface $builder, string $entityClassName, ?string $entityTranslationRelationName): void
    {
        $builder
            ->add(
                'entity',
                EntityType::class,
                [
                    'class' => $entityClassName,
                    'label' => false,
                    'placeholder' => '---',
                    'translation_domain' => false,
                    'required' => false,
                    'query_builder' => static function (EntityRepository $er) use ($entityTranslationRelationName): QueryBuilder {
                        $qb = $er->createQueryBuilder('qb')->select('qb');
                        if ($entityTranslationRelationName) {
                            $qb->addSelect(['t'])->leftJoin("qb.$entityTranslationRelationName", 't');
                        }

                        return $qb;
                    },
                ],
            );
    }

    /**
     * @param array|FormFieldDefinition[] $fieldDefinitions
     *
     * @throws AlreadySubmittedException
     * @throws LogicException
     * @throws UnexpectedTypeException
     */
    private function addField(array $fieldDefinitions, string $columnName, FormEvent $event): void
    {
        $definition = $fieldDefinitions[ColumnNameHelper::removeTranslationSuffix($columnName)] ?? null;

        $definition
            ? $event->getForm()->add($columnName, $definition->getClass(), $definition->getOptions())
            : $event->getForm()->add($columnName, TextType::class);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var MatrixRecord $entity */
        $entity = $form->getData();
        $selectedValue = $entity->entityId;

        foreach ($view['entity']->vars['choices'] ?? [] as $index => $choice) {
            if ($choice->value === $selectedValue) {
                $view['entity']->vars['choices'][$index]->attr['selected'] = 'selected';
            }
        }
    }
}

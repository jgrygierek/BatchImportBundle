<?php

declare(strict_types=1);

namespace JG\BatchEntityImportBundle\Tests\Unit\Form\Type;

use Generator;
use JG\BatchEntityImportBundle\Form\Type\ArrayTextType;
use stdClass;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use UnexpectedValueException;

class ArrayTextTypeTest extends TypeTestCase
{
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator
            ->method('trans')
            ->willReturnCallback(static fn ($key, $params) => sprintf('separator: "%s"', $params['%separator%'] ?? ''));

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $type = new ArrayTextType($this->translator);

        return [
            new PreloadedExtension([$type], []),
        ];
    }

    public function testConfigureOptions(): void
    {
        $type = new ArrayTextType($this->translator);
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertFalse($options['compound']);
        $this->assertEquals('|', $options['separator']);
    }

    public function testSubmitValidDataBeforeTransform(): void
    {
        $formData = 'a|b|c';
        $form = $this->factory->create(ArrayTextType::class);
        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals('a|b|c', $form->getData());
    }

    /**
     * @dataProvider transformDataProvider
     * @dataProvider transformDataToEmptyStringProvider
     * @dataProvider transformDataWithWrongSeparatorProvider
     */
    public function testTransform(string $separator, array $data, $expected): void
    {
        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), ['separator' => $separator]);

        $this->assertSame($expected, $type->transform($data));
    }

    public static function transformDataProvider(): Generator
    {
        yield ['|', [null], ''];
        yield ['|', ['a', 'b', 'c'], 'a|b|c'];
        yield ['|', ['a', 'b', null], 'a|b|'];
        yield ['|', ['a', '', 'c'], 'a||c'];
        yield ['|', ['a', null, 'c'], 'a||c'];
        yield ['|', ['a', '', 'c'], 'a||c'];
        yield ['|', [null, 'b', 'c'], '|b|c'];
        yield ['|', ['', 'b', 'c'], '|b|c'];
        yield ['|', ['', '', ''], '||'];
        yield [';', [' '], ' '];
        yield [';', ['a', 'b', 'c'], 'a;b;c'];
        yield [';', ['a', 'b', null], 'a;b;'];
        yield [';', ['a', '', 'c'], 'a;;c'];
        yield [';', ['a', null, 'c'], 'a;;c'];
        yield [';', ['a', '', 'c'], 'a;;c'];
        yield [';', [null, 'b', 'c'], ';b;c'];
        yield [';', ['', 'b', 'c'], ';b;c'];
        yield [';', ['', '', ''], ';;'];
    }

    public static function transformDataToEmptyStringProvider(): Generator
    {
        yield ['|', [], ''];
        yield ['|', [''], ''];
        yield ['|', [null], ''];
        yield [';', [], ''];
        yield [';', [''], ''];
        yield [';', [null], ''];
    }

    public static function transformDataWithWrongSeparatorProvider(): Generator
    {
        yield ['|', ['a;b;c'], 'a;b;c'];
        yield [';', ['a|b|c'], 'a|b|c'];
    }

    /**
     * @dataProvider transformDataWithDefaultSeparatorProvider
     * @dataProvider transformDataWithDefaultSeparatorToEmptyStringProvider
     */
    public function testTransformWithDefaultSeparator(mixed $data, mixed $expected): void
    {
        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), []);

        $this->assertSame($expected, $type->transform($data));
    }

    public static function transformDataWithDefaultSeparatorProvider(): Generator
    {
        yield [[' '], ' '];
        yield [['a', 'b', 'c'], 'a|b|c'];
        yield [['a', 'b', null], 'a|b|'];
        yield [['a', 'b', ''], 'a|b|'];
        yield [['a', null, 'c'], 'a||c'];
        yield [['a', '', 'c'], 'a||c'];
        yield [[null, 'b', 'c'], '|b|c'];
        yield [['', 'b', 'c'], '|b|c'];
        yield [['', '', ''], '||'];
    }

    public static function transformDataWithDefaultSeparatorToEmptyStringProvider(): Generator
    {
        yield [[], ''];
        yield [[''], ''];
        yield [[null], ''];
    }

    /**
     * @dataProvider transformWrongValueExceptionDataProvider
     */
    public function testTransformWrongValueException(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Only arrays are allowed');

        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), []);

        $type->transform($value);
    }

    public static function transformWrongValueExceptionDataProvider(): Generator
    {
        yield [''];
        yield [1234];
        yield ['lorem ipsum'];
        yield [null];
        yield [new stdClass()];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     * @dataProvider reverseTransformDataWithEmptySeparatorProvider
     * @dataProvider reverseTransformDataWithWrongSeparatorProvider
     */
    public function testReverseTransform(?string $separator, string $data, mixed $expected): void
    {
        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), ['separator' => $separator]);

        $this->assertSame($expected, $type->reverseTransform($data));
    }

    public static function reverseTransformDataProvider(): Generator
    {
        yield ['|', '', []];
        yield ['|', ' ', [' ']];
        yield ['|', '||', ['', '', '']];
        yield ['|', 'a|b|c', ['a', 'b', 'c']];
        yield ['|', 'a|b|', ['a', 'b', '']];
        yield ['|', 'a||c', ['a', '', 'c']];
        yield ['|', '|b|c', ['', 'b', 'c']];
        yield [';', '', []];
        yield [';', ' ', [' ']];
        yield [';', ';;', ['', '', '']];
        yield [';', 'a;b;c', ['a', 'b', 'c']];
        yield [';', 'a;b;', ['a', 'b', '']];
        yield [';', 'a;;c', ['a', '', 'c']];
        yield [';', ';b;c', ['', 'b', 'c']];
    }

    public static function reverseTransformDataWithEmptySeparatorProvider(): Generator
    {
        yield ['', '', []];
        yield ['', ' ', [' ']];
        yield ['', 'a|b|c', ['a', 'b', 'c']];
        yield ['', 'a;b;c', ['a;b;c']];
        yield [null, '', []];
        yield [null, ' ', [' ']];
        yield [null, 'a|b|c', ['a', 'b', 'c']];
        yield [null, 'a;b;c', ['a;b;c']];
    }

    public static function reverseTransformDataWithWrongSeparatorProvider(): Generator
    {
        yield ['|', 'a;b;c', ['a;b;c']];
        yield [';', 'a|b|c', ['a|b|c']];
    }

    /**
     * @dataProvider reverseTransformDataWithDefaultSeparatorProvider
     */
    public function testReverseTransformWithDefaultSeparator(string $data, mixed $expected): void
    {
        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), []);

        $this->assertSame($expected, $type->reverseTransform($data));
    }

    public static function reverseTransformDataWithDefaultSeparatorProvider(): Generator
    {
        yield ['', []];
        yield [' ', [' ']];
        yield ['a|b|c', ['a', 'b', 'c']];
        yield ['a|b|', ['a', 'b', '']];
        yield ['a||c', ['a', '', 'c']];
        yield ['|b|c', ['', 'b', 'c']];
        yield ['a;b;c', ['a;b;c']];
        yield ['a;b;', ['a;b;']];
        yield ['a;;c', ['a;;c']];
        yield [';b;c', [';b;c']];
    }

    /**
     * @dataProvider reverseTransformWrongValueExceptionDataProvider
     */
    public function testReverseTransformWrongValueException(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Only strings are allowed');

        $type = new ArrayTextType($this->translator);
        $type->buildForm($this->createMock(FormBuilderInterface::class), []);

        $type->reverseTransform($value);
    }

    public static function reverseTransformWrongValueExceptionDataProvider(): Generator
    {
        yield [[]];
        yield [123];
        yield [null];
        yield [new stdClass()];
    }

    public function testBuildView(): void
    {
        $type = new ArrayTextType($this->translator);
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $type->buildView($view, $form, ['separator' => ';']);

        $this->assertArrayHasKey('help', $view->vars);
        $this->assertEquals('separator: ";"', $view->vars['help']);
    }
}

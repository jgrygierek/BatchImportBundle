# BatchEntityImportBundle

![Code Style](https://github.com/jgrygierek/BatchEntityImportBundle/workflows/Code%20Style/badge.svg)
![Tests](https://github.com/jgrygierek/BatchEntityImportBundle/workflows/Tests/badge.svg)
![Code Coverage](https://img.shields.io/codecov/c/github/jgrygierek/BatchEntityImportBundle/master)
![PHP Versions](https://img.shields.io/badge/PHP->=8.1-blue)
![Symfony Versions](https://img.shields.io/badge/Symfony-5.4--7.*-blue)
[![SymfonyInsight](https://insight.symfony.com/projects/ad63558e-3612-434f-a93d-0fc5fce2dd20/mini.svg)](https://insight.symfony.com/projects/ad63558e-3612-434f-a93d-0fc5fce2dd20)

Importing entities with preview and edit features for Symfony.

* Data can be **viewed and edited** before saving to database.
* Supports **inserting** new records and **updating** existing ones.
* Supported extensions: **CSV, XLS, XLSX, ODS**.
* Supports translations from **KnpLabs Translatable** extension.
* The code is divided into smaller methods that can be easily replaced if you want to change something.
* Columns names are required and should be added as header (first row).
* If column does not have name provided, will be removed from loaded data.

![Select File](docs/select_file.png)
![Edit Matrix](docs/edit_matrix.png)

## Documentation
* [Installation](#installation)
* [Configuration class](#configuration-class)
  * [Basic configuration class](#basic-configuration-class)
  * [Fields definitions](#fields-definitions)
  * [Matrix validation](#matrix-validation)
  * [Passing services to configuration class](#passing-services-to-configuration-class)
  * [Show & hide entity override column](#show--hide-entity-override-column)
  * [Set allowed file extensions](#set-allowed-file-extensions)
  * [Optimizing queries](#optimizing-queries)
* [Creating controller](#creating-controller)
* [Translations](#translations)
* [Overriding templates](#overriding-templates)
    * [Global templates](#global-templates)
    * [Controller-specific templates](#controller-specific-templates)
    * [Main layout](#main-layout)
    * [Additional data](#additional-data)
* [Updating entities](#updating-entities)
* [Importing data to array field](#importing-data-to-array-field)
* [Full example of CSV file](#full-example-of-csv-file)

## Installation

Install package via composer:

```
composer require jgrygierek/batch-entity-import-bundle
```

Add entry to `bundles.php` file:

```
JG\BatchEntityImportBundle\BatchEntityImportBundle::class => ['all' => true],
```

## Configuration class

To define how the import function should work, you need to create a configuration class.

### Basic configuration class

In the simplest case it will contain only class of used entity.

```php
namespace App\Model\ImportConfiguration;

use App\Entity\User;
use JG\BatchEntityImportBundle\Model\Configuration\AbstractImportConfiguration;

class UserImportConfiguration extends AbstractImportConfiguration
{
    public function getEntityClassName(): string
    {
        return User::class;
    }
}
```

Then register it as a service:

```yaml
services:
  App\Model\ImportConfiguration\UserImportConfiguration: ~
```

### Fields definitions

If you want to change types of rendered fields, instead of using default ones,
you have to override method in your import configuration. If name of field contains spaces, you should use underscores instead.

To avoid errors during data import, you can add here validation rules.

```php

use JG\BatchEntityImportBundle\Model\Form\FormFieldDefinition;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Length;

public function getFieldsDefinitions(): array
{
    return [
        'age' => new FormFieldDefinition(
            IntegerType::class,
            [
                'attr' => [
                    'min' => 0,
                    'max' => 999,
                ],
            ]
        ),
        'name' => new FormFieldDefinition(TextType::class),
        'description' => new FormFieldDefinition(
            TextareaType::class,
            [
                'attr' => [
                    'rows' => 2,
                ],
                'constraints' => [new Length(['max' => 255])],
            ]
        ),
    ];
}
```

### Matrix validation

This bundle provides two new validators.

1) **DatabaseEntityUnique** validator can be used to check if record data does not exist yet in database.
2) **MatrixRecordUnique** validator can be used to check duplication without checking database, just only matrix records values.

Names of fields should be the same as names of columns in your uploaded file. With one exception! If name contains spaces, you should use underscores instead.

```php
use JG\BatchEntityImportBundle\Validator\Constraints\DatabaseEntityUnique;
use JG\BatchEntityImportBundle\Validator\Constraints\MatrixRecordUnique;

public function getMatrixConstraints(): array
{
    return [
        new MatrixRecordUnique(['fields' => ['field_name']]),
        new DatabaseEntityUnique(['entityClassName' => $this->getEntityClassName(), 'fields' => ['field_name']]),
    ];
}
```

### Passing services to configuration class

If you want to pass some additional services to your configuration, just override constructor.

```php
public function __construct(EntityManagerInterface $em, TestService $service)
{
    parent::__construct($em);

    $this->testService = $service;
}
```

### Show & hide entity override column

If you want to hide/show an entity column that allows you to override entity `default: true`,
you have to override this method in your import configuration.

```php
public function allowOverrideEntity(): bool
{
    return true;
}
```

### Set allowed file extensions

By default, allowed file extensions are set to `'csv', 'xls', 'xlsx', 'ods'`.
However, if you want to change it, you can override this method in your import configuration.

```php
  public function getAllowedFileExtensions(): array
  {
      return ['csv', 'xls', 'xlsx', 'ods'];
  }
```

### Optimizing queries

If you use **KnpLabs Translatable** extension for your entity, probably you will notice increased number of queries, because of Lazy Loading.

To optimize this, you can use `getEntityTranslationRelationName()` method to pass the relation name to the translation.

```php
public function getEntityTranslationRelationName(): ?string
{
    return 'translations';
}
```

## Creating controller

Create controller with some required code.

This is just an example, depending on your needs you can inject services in different ways.

To enable automatic passing configuration service to your controller, please use `ImportConfigurationAutoInjectInterface` and `ImportConfigurationAutoInjectTrait`.

```php
namespace App\Controller;

use App\Model\ImportConfiguration\UserImportConfiguration;
use JG\BatchEntityImportBundle\Controller\ImportConfigurationAutoInjectInterface;
use JG\BatchEntityImportBundle\Controller\ImportConfigurationAutoInjectTrait;
use JG\BatchEntityImportBundle\Controller\ImportControllerTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportController extends AbstractController implements ImportConfigurationAutoInjectInterface
{
    use ImportControllerTrait;
    use ImportConfigurationAutoInjectTrait;

    /**
     * @Route("/user/import", name="user_import")
     */
    public function import(Request $request, ValidatorInterface $validator): Response
    {
        return $this->doImport($request, $validator);
    }

    /**
     * @Route("/user/import/save", name="user_import_save")
     */
    public function importSave(Request $request, TranslatorInterface $translator): Response
    {
        return $this->doImportSave($request, $translator);
    }

    protected function redirectToImport(): RedirectResponse
    {
       return $this->redirectToRoute('user_import');
    }

    protected function getMatrixSaveActionUrl(): string
    {
       return $this->generateUrl('user_import_save');
    }

    protected function getImportConfigurationClassName(): string
    {
       return UserImportConfiguration::class;
    }
}
```

## Translations

This bundle supports KnpLabs Translatable behavior.

To use this feature, every column with translatable values should be suffixed with locale, for example:
* `name:en`
* `description:pl`
* `title:ru`

If suffix will be added to non-translatable entity, field will be skipped.

If suffix will be added to translatable entity, but field will not be found in translation class, field will be skipped.

## Overriding templates

#### Global templates

You have two ways to override templates globally:

- **Configuration** - just change paths to templates in your configuration file. 
Values in this example are default ones and will be used if nothing will be change.

```yaml
batch_entity_import:
    templates:
        select_file: '@BatchEntityImport/select_file.html.twig'
        edit_matrix: '@BatchEntityImport/edit_matrix.html.twig'
        layout: '@BatchEntityImport/layout.html.twig'
```

- **Bundle directory** - put your templates in this directory:

```
templates/bundles/BatchEntityImportBundle
```

#### Controller-specific templates

If you have controller-specific templates, you can override them in controller:

```php
protected function getSelectFileTemplateName(): string
{
    return 'your/path/to/select_file.html.twig';
}

protected function getMatrixEditTemplateName(): string
{
    return 'your/path/to/edit_matrix.html.twig';
}
```

#### Main layout

Block name used in templates is `batch_entity_import_content`, so probably there will be need to override it a bit.
You can create a new file with content similar to the given example. Then just use it instead of original layout file.

```twig
{% extends path/to/your/layout.html.twig %}

{% block your_real_block_name %}
    {% block batch_entity_import_content %}{% endblock %}
{% endblock %}
```

Then you just have to override it in bundle directory, or change a path to layout in your configuration.

#### Additional data

If you want to add some specific data to the rendered view, just override these methods in your controller:

```php
protected function prepareSelectFileView(FormInterface $form): Response
{
    return $this->prepareView(
        $this->getSelectFileTemplateName(),
        [
            'form' => $form->createView(),
        ]
    );
}

protected function prepareMatrixEditView(FormInterface $form, Matrix $matrix, bool $manualSubmit = false): Response
{
    if ($manualSubmit) {
        $this->manualSubmitMatrixForm($form, $matrix);
    }

    $configuration = $this->getImportConfiguration();

    return $this->prepareView(
        $this->getMatrixEditTemplateName(),
        [
            'header_info' => $matrix->getHeaderInfo($configuration->getEntityClassName()),
            'data' => $matrix->getRecords(),
            'form' => $form->createView(),
            'importConfiguration' => $configuration,
        ]
    );
}
```

## Updating entities

If you want to update your entities:
- Set `allowOverrideEntity` to `true` in your import configuration file. 
- Then in your import file:
  - Add `entity_id` in header and:
    - Add entity ID to row
    - Leave it empty (if you want to set it manually or import it as new record)
  - Or if you don't want to add `entity_id` header, you can still manually set each entity to override.

#### CSV file

```csv
entity_id,user_name
2,user_1
,user_2
10,user_3
```

## Importing data to array field

If your entity has an array field, and you want to import data from CSV file to it, this is how you can do it.

- Default separator is set to `|`.
- Only one-dimensional arrays are allowed.
- Keys are not allowed.
- **IMPORTANT!** There are limitations:
  - There is no possibility to import array with one empty element, for example:
    - ['']
    - [null]
  - But arrays with at least 2 such elements are allowed:
    - ['', '']
    - [null, null]
    - ['', null]
  - It is due of mapping CSV data to array:
    - Empty value in CSV is equal to `[]` 
    - If we have default separator, `|` value in CSV is equal to `['', '']`

#### Entity

- Allowed entity field types:
  - `json`
  - `array`
  - `simple_array`

```php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Column(type: 'json']
    private array $roles = [];
}
```

#### Import configuration

* You have to add a field definition with a custom `ArrayTextType` type. If you skip this configuration, `TextType` will be used as default.
* You can set here your own separator.

```php
use JG\BatchEntityImportBundle\Form\Type\ArrayTextType;
use JG\BatchEntityImportBundle\Model\Form\FormFieldDefinition;

public function getFieldsDefinitions(): array
{
    return [
        'roles' => new FormFieldDefinition(
            ArrayTextType::class,
            [
                'separator' => '&',
            ]
        ),
    ];
}
```

#### CSV file

```csv
user_name,roles
user_1,USER&ADMIN&SUPER_ADMIN
user_2,USER
user_3,SUPER_ADMIN
```


## Full example of CSV file

```csv
entity_id,user_name,age,email,roles,country:en,name:pl
1,user_1,21,user_1@test.com,USER&ADMIN&SUPER_ADMIN,Poland,Polska
3, user_2,34,user_2@test.com,USER,England,Anglia
,user_3,56,user_3@test.com,SUPER_ADMIN,Germany,Niemcy
```

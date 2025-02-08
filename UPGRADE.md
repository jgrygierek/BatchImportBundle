# UPGRADE TO 3.7

### Preset entity id before updating - [go to the documentation](README.md#updating-entities)

# UPGRADE TO 3.6

### Set allowed file extensions - [go to the documentation](README.md#set-allowed-file-extensions)

# UPGRADE TO 3.5

### Import data to array - [go to the documentation](README.md#importing-data-to-array-field)

# UPGRADE TO 3.1

## CSV File
- Now CSV file can contain spaces and dashes as a header name, for example "my column name" or "my-column-name".

## Import Configuration class
- When header name contains spaces we should use underscores instead of spaces when defining fields names in fields definitions and in constraints.


# UPGRADE TO 3.0

## Controller
* Passing configuration class by `getSubscribedServices()` was removed. Now it is only possible by autoconfiguration.


# UPGRADE TO 2.5

## Import Configuration class
- Added new validator to check matrix record data uniqueness in database.
```php
use JG\BatchEntityImportBundle\Validator\Constraints\DatabaseEntityUnique;

public function getMatrixConstraints(): array
{
    return [
        new DatabaseEntityUnique(['entityClassName' => $this->getEntityClassName(), 'fields' => ['field_name']]),
    ];
}
```

# UPGRADE TO 2.4

## Import Configuration class
- Added new validator to check matrix record data uniqueness.
```php
use JG\BatchEntityImportBundle\Validator\Constraints\MatrixRecordUnique;

public function getMatrixConstraints(): array
{
    return [
        new MatrixRecordUnique(['fields' => ['field_name']]),
    ];
}
```

## Controller
- List of options passed to form in `createMatrixForm()` method, should contain new `constraints` element:
`'constraints' => $importConfiguration->getMatrixConstraints()`

# UPGRADE TO 2.3

## Controller
- Passing configuration class by `getSubscribedServices()` method is not needed anymore and will be removed in the future.
- To make sure that configuration class will be injected automatically:
  - Interface `JG\BatchEntityImportBundle\Controller\ImportConfigurationAutoInjectInterface` should be implemented.
  - Trait `JG\BatchEntityImportBundle\Controller\ImportConfigurationAutoInjectTrait` should be used to add needed methods.


# UPGRADE TO 2.2

## Import Configuration class
- Now configuration class should be always registered as a service:
```yaml
services:
    App\Model\ImportConfiguration\UserImportConfiguration: ~
```

## Controller
- Entity Manager is no longer passed as an argument of actions.

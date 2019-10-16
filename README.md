# Field Query

<!--
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
-->

Component which provides the syntax for data-fields, and parses them to obtain their information

## Install

Via Composer

``` bash
$ composer require getpop/field-query dev-master
```

**Note:** Your `composer.json` file must have the configuration below to accept minimum stability `"dev"` (there are no releases for PoP yet, and the code is installed directly from the `master` branch):

```javascript
{
    ...
    "minimum-stability": "dev",
    "prefer-stable": true,
    ...
}
```

## Usage

```php
use PoP\FieldQuery\Facades\Query\FieldQueryInterpreterFacade;

$fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
$field = $fieldQueryInterpreter->getField($fieldName, $fieldArgs);
// Other functions from FieldQueryInterpreter
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email leo@getpop.org instead of using the issue tracker.

## Credits

- [Leonardo Losoviz][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/getpop/field-query.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/getpop/field-query/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/getpop/field-query.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/getpop/field-query.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/getpop/field-query.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/getpop/field-query
[link-travis]: https://travis-ci.org/getpop/field-query
[link-scrutinizer]: https://scrutinizer-ci.com/g/getpop/field-query/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/getpop/field-query
[link-downloads]: https://packagist.org/packages/getpop/field-query
[link-author]: https://github.com/leoloso
[link-contributors]: ../../contributors

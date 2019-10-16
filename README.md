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
// To create a field from its elements
$field = $fieldQueryInterpreter->getField($fieldName, $fieldArgs);

// To retrieve the elements from a field
$fieldName = $fieldQueryInterpreter->getFieldName($field);
$fieldArgs = $fieldQueryInterpreter->getFieldArgs($field);
$fieldDirectives = $fieldQueryInterpreter->getFieldDirectives($field);
// ...
// Other functions from FieldQueryInterpreter
```

## Description

Syntax to retrieve data from the server for PoP applications. It can be used to load data for the components within the application itself (through the [Component Model](https://github.com/getpop/component-model)), or externally through an API (such as [PoP API](https://github.com/getpop/api), [GraphQL API](https://github.com/getpop/api-graphql)and [REST API](https://github.com/getpop/api-rest)).

The syntax is heavily influenced by GraphQL. However, it differs that, because in PoP the query can be provided as a URL parameter (instead of being provided through the body of the request, as in GraphQL), the syntax must be easy to write, and easy to understand, fitting in a single line.

## Syntax

[Similar to GraphQL](https://graphql.org/learn/queries/#fields), the query describes a set of “fields”, where each field can contain the following elements:

- **The field name:** What data to retrieve
- **Field arguments:** How to filter the data, or format the results
- **Field alias:** How to name the field in the response
- **Field directives:** To change the behaviour of how to execute the operation

Differently than GraphQL, a field can also contain the following elements:

- **Bookmarks:** To keep loading data from an already-defined field
- **Skip output if null:** To ignore the output if the value of the field is null
- **Nested fields:** The response of a field can be used as input to another field, through its arguments or field directives

From the composing elements, only the field name is mandatory; all others are optional. A field is composed in this order:

1. The field name
2. Arguments: `(...)`
3. Bookmark: `[...]`
4. Alias: `@...` (if the bookmark is also present, it is placed inside)
5. Skip output if null: `?`
6. Directives: directive name and arguments: `<directiveName(...)>`

The field looks like this:

```
fieldName(fieldArgs)[@alias]?<fieldDirective(directiveArgs)>
```

To retrieve several fields in the same query, we join them using symbol `,`:

```
fieldName1@alias1,fieldName2(fieldArgs2)[@alias2]?<fieldDirective2>
```

### Retrieving properties from a node

Separate the properties to fetch using symbol `|`.

_**In GraphQL**:_

```graphql
query {
  id
  __schema
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql//?query=id|__schema)):_

```
/?query=id|__schema
```

### Retrieving nested properties

To fetch relational data (such as "the posts's author's name and email"), delineate the path to the properties using symbol `.`.


### Adding fields

On a first level, the query is compose like this:

- Fields are separated with `,`
- The 
- 

For instance, the following GraphQL query:

```graphql
query {
  posts {
    id
    title
    url
    content
    comments {
        id
        content
        date
        author {
        id
        name
        url
        posts {
            id
            title
            url
        }
      }
    }
  }
}
```

Is equivalent to the following single-line query:

```
posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url
```

Our endpoint therefore becomes:

[/api/graphql/?query=posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url)

### Field arguments

A field can have arguments: An array of `name:value` properties, appended next to the field name enclosed with `()` and separated with `,`, which modify the output (results, formatting, etc) from the field. 

Examples: 

- Order posts by title: [posts(order:title|asc)](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc).id|title|url|date)
- Search "template" and limit it to 3 results: [posts(searchfor:template,limit:3)](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3).id|title|url|date)
- Format a date: [posts.date(format:d/m/Y)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y))

Argument names can also be deduced from the schema. Then, only the `value` needs be provided: 

- Format a date: [posts.date(d/m/Y)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(d/m/Y))

### Aliases

A field is, by default, output under its own definition (for instance, [posts(order:title|asc)](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc).id|title|url|date) is output under property `posts(order:title|asc)`). An “alias”, which is a property name prepended with `@`, allows to change this property to anything we desire.

Examples:

- [posts(order:title|asc)@orderedposts](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc)@orderedposts.id|title|url|date)
- [posts.date(format:d/m/Y)@formatteddate](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y)@formatteddate)

### Bookmarks

The query allows to iterate down a path using `.` (for instance: [posts.comments.author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts.comments.author.id|name)). We can assign a “bookmark” to any specific level, as to start iterating from there once again. To use it, we place any name surrounded by `[...]` after the path level, and then the same name, also surrounded by `[...]`, as the root path level to iterate from there.

Example:

- [posts.comments[comments].author.id|name,[comments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[comments].author.id|name,[comments].post.id|title)

### Bookmark with Alias

Bookmarks can be combined with aliases by adding `@` to the name surrounded by `[...]`.

Example:

- [posts.comments[@postcomments].author.id|name,[postcomments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[@postcomments].author.id|name,[postcomments].post.id|title)

### Variables

We can use “variables”, which are names prepended with `$`, to pass field argument values defined through URL parameters: Either under URL parameter with the variable name, or under URL parameter `variables` and then the variable name.

Example:

- [posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template)

### Fragments

We can use “fragments”, which must be prepended using `--`, to re-use query sections.

Example:

- [posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title](https://nextapi.getpop.org/api/graphql/?query=posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title)

### Directives

A “directive” enables to modify the response from one or many fields, in any way. They must be surrounded by `<...>` and, if more than one directive is provided, separated by `,`. A directive can also receive arguments, with a syntax similar to field arguments: they are surrounded by `(...)`, and its pairs of `key:value` are separated by `,`.

Examples:

- [posts.id|title|url<include(if:$include)>&variables[include]=true](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=true)
- [posts.id|title|url<include(if:$include)>&variables[include]=](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=)

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

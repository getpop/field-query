# Field Query

<!--
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
-->

Syntax to retrieve data from the server for PoP applications, and to grant URI-based server-side caching capabilities to a GraphQL API. 

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
// Other functions listed in FieldQueryInterpreterInterface
```

## Why

The GraphQL query generally spans multiple lines, and it is provided through the body of the request instead of through URL params. As a result, it is difficult to cache the results from a GraphQL query in the server. In order to support server-side caching on GraphQL, we can attempt to provide the query through the URL instead, as to use standard mechanisms which cache a page based on the URL as its unique ID.

The syntax described and implemented in this project is a re-imagining of the GraphQL syntax, supporting all the same elements (field arguments, variables, aliases, fragments, directives, etc), however designed to be easy to write, and easy to read and understand, in a single line, so it can be passed as a URL param.

Being able to pass the query as a URL param has, in turn, several other advantages:

- It removes the need for a client-side library to convert the GraphQL query into the required format (such as [Relay](https://relay.dev/docs/en/graphql-in-relay)), leading to performance improvements and reduced amount of code to maintain
- The GraphQL API becomes easier to consume (same as REST), and avoids depending on a special client (such as [GraphiQL](https://github.com/graphql/graphiql)) to visualize the results of the query

## Who uses it

PoP uses this syntax natively: To load data in each component within the application itself (as done by the [Component Model](https://github.com/getpop/component-model)), and to load data from an API through URL param `query` (as done by the [PoP API](https://github.com/getpop/api)).

A GraphQL server can implement this syntax as to support URI-based server-side caching. To achieve this, a service must translate the query from this syntax to the corresponding [GraphQL syntax](https://graphql.org/learn/queries/), and then pass the translated query to the GraphQL engine. This development is currently [in progress](https://github.com/getpop/api-graphql/issues/1) for the [GraphQL API for PoP](https://github.com/getpop/api-graphql)).

## Syntax

[Similar to GraphQL](https://graphql.org/learn/queries/#fields), the query describes a set of “fields”, where each field can contain the following elements:

- **The field name:** What data to retrieve
- **Field arguments:** How to filter the data, or format the results
- **Field alias:** How to name the field in the response
- **Field directives:** To change the behaviour of how to execute the operation

Differently than GraphQL, a field can also contain the following elements:

- **Property names in the field arguments may be optional:** To simplify passing arguments to the field
- **Bookmarks:** To keep loading data from an already-defined field
- **Operators and Nested fields:** The response of a field can be used as input to another field, through its arguments or field directives
- **Skip output if null:** To ignore the output if the value of the field is null

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

To retrieve several fields in the same query, we join them using `,`:

```
fieldName1@alias1,fieldName2(fieldArgs2)[@alias2]?<fieldDirective2>
```

### Retrieving properties from a node

Separate the properties to fetch using `|`.

_**In GraphQL**:_

```graphql
query {
  id
  __schema
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=id|__schema)):_

```
/?query=id|__schema
```

### Retrieving nested properties

To fetch relational or nested data, describe the path to the property using `.`.

_**In GraphQL**:_

```graphql
query {
  posts {
    author {
      id
    }
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.author.id)):_

```
/?query=posts.author.id
```

We can use `|` to bring more than one property when reaching the node:

_**In GraphQL**:_

```graphql
query {
  posts {
    author {
      id
      name
      url
    }
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.author.id|name|url)):_

```
/?query=posts.author.id|name|url
```

Symbols `.` and `|` can be mixed together to also bring properties along the path:

_**In GraphQL**:_

```graphql
query {
  posts {
    id
    title
    author {
      id
      name
      url
    }
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|author.id|name|url)):_

```
/?query=posts.id|title|author.id|name|url
```

### Adding fields

Combine different fields by joining them using `,`.

_**In GraphQL**:_

```graphql
query {
  posts {
    author {
      id
      name
      url
    }
    comments {
      id
      content
    }
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.author.id|name|url,posts.comments.id|content)):_

```
/?query=posts.author.id|name|url,posts.comments.id|content
```

### Field arguments

Array of properties to filter the results (when applied to a property along a path), or modify the output (when applied to a property on a leaf node) from the field. These are enclosed using `()`, defined using `:` to separate the property name from the value (becoming `name:value`), and separated using `,`.

Values do not need be enclosed using quotes `"..."`.

_Filtering results **in GraphQL**:_

```graphql
query {
  posts(search: "something") {
    id
    title
    date
  }
}
```

_Filtering results **in PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template).id|title|date)):_

```
/?query=posts(search:something).id|title|date
```

_Formatting output **in GraphQL**:_

```graphql
query {
  posts {
    id
    title
    date(format: "d/m/Y")
  }
}
```

_Formatting output **in PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date(format:d/m/Y))):_

```
/?query=posts.id|title|date(format:d/m/Y)
```

### Optional property name in field arguments

Defining the argument name can be ignored if it can be deduced from the schema (for instance, the name can be deduced from the position of the property within the arguments in the schema definition).

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date(d/m/Y))):_

```
/?query=posts.id|title|date(d/m/Y)
```

### Aliases

An alias defines under what name to output the field. The alias name must be prepended with `@`:

_**In GraphQL**:_

```graphql
query {
  posts {
    id
    title
    formattedDate: date(format: "d/m/Y")
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date(d/m/Y)@formattedDate)):_

```
/?query=posts.id|title|date(d/m/Y)@formattedDate
```

Please notice that aliases are optional, differently than in GraphQL. [In GraphQL](https://graphql.org/learn/queries/#aliases), because the field arguments are not part of the field in the response, when querying the same field with different arguments it is required to use an alias to differentiate them. In PoP, however, field arguments are part of the field in the response, which already differentiates the fields.

_**In GraphQL**:_

```graphql
query {
  posts {
    id
    title
    date: date
    formattedDate: date(format: "d/m/Y")
  }
}
```

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date|date(d/m/Y))):_

```
/?query=posts.id|title|date|date(d/m/Y)
```

### Bookmarks

When iterating down a field path, loading data from different sub-branches is solved elegantly in GraphQL:

_**In GraphQL**:_

```graphql
query {
  users {
    posts {
      author {
        id
        name
      }
      comments {
        id
        content
      }
    }
  }
}
```

In PoP, however, the query can easily become very verbose, because when combining fields with `,` it starts iterating the path again all the way from the root:

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=users.posts.author.id|name,users.posts.comments.id|content)):_

```
/?query=users.posts.author.id|name,users.posts.comments.id|content
```

Bookmarks help address this problem by creating a shortcut to a path, so we can conveniently keep loading data from that point on. To define the bookmark, its name is enclosed with `[...]` when iterating down the path, and to use it, its name is similarly enclosed with `[...]`:

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=users.posts[userposts].author.id|name,[userposts].comments.id|content)):_

```
/?query=users.posts[userposts].author.id|name,[userposts].comments.id|content
```

### Bookmark with Alias

When we need to define both a bookmark to a path, and an alias to output the field, these 2 must be combined: The alias, prepended with `@`, is placed within the bookmark delimiters `[...]`.

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=users.posts[@userposts].author.id|name,[userposts].comments.id|content)):_

```
/?query=users.posts[@userposts].author.id|name,[userposts].comments.id|content
```

### Variables

Variables can be used to input values to field arguments. While [in GraphQL](https://graphql.org/learn/queries/#variables) the values to resolve to are defined within the body (in a separate dictionary than the query), in PoP these are retrieved from the request (`$_GET` or `$_POST`). 

The variable name must be prepended with `$`, and its value in the request can be defined either directly under the variable name, or under entry `variables` and then the variable name. 

_API call **in GraphQL**:_

```html
{
  "query":"query ($format: String) {
    posts {
      id
      title
      date(format: $format)
    }
  }",
  "variables":"{
    \"format\":\"d/m/Y\"
  }"
}
```

_**In PoP** ([example 1](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date($format)&format=d/m/Y), [example 2](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date($format)&variables[format]=d/m/Y)):_

```
1. /?query=posts.id|title|date($format)&format=d/m/Y
2. /?query=posts.id|title|date($format)&variables[format]=d/m/Y
```

### Fragments

Fragments enable to re-use query sections. Similar to variables, their resolution is defined in the request (`$_GET` or `$_POST`). Unlike [in GraphQL](https://graphql.org/learn/queries/#fragments), the fragment does not need to indicate on which schema type it operates.

The fragment name must be prepended with `--`, and the query they resolve to can be defined either directly under the fragment name, or under entry `fragments` and then the fragment name. 

_**In GraphQL**:_

```graphql
query {
  users {
    ...userData
    posts {
      comments {
        author {
          ...userData
        }
      }
    }
  }
}

fragment userData on User {
  id
  name
  url
}
```

_**In PoP** ([example 1](https://nextapi.getpop.org/api/graphql/?query=users.--userData|posts.comments.author.--userData&userData=id|name|url), [example 2](https://nextapi.getpop.org/api/graphql/?query=users.--userData|posts.comments.author.--userData&fragments[userData]=id|name|url)):_

```
1. /?query=users.--userData|posts.comments.author.--userData&userData=id|name|url
2. /?query=users.--userData|posts.comments.author.--userData&fragments[userData]=id|name|url
```

### Directives

A directive enables to modify if/how the operation to fetch data is executed. Each field accepts many directives, each of them receiving its own arguments to customize its behaviour. The set of directives is surrounded by `<...>`, the directives within must be separated by `,`, and their arguments follows the same syntax as field arguments: they are surrounded by `(...)`, and its pairs of `name:value` are separated by `,`.

_**In GraphQL**:_

```graphql
query {
  posts {
    id
    title
    featuredimage @include(if: $addFeaturedImage) {
      id
      src
    }
  }
}
```

_**In PoP** ([example 1](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|featuredimage<include(if:$include)>.id|src&include=true), [example 2](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|featuredimage<include(if:$include)>.id|src&include=), [example 3](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|featuredimage<skip(if:$skip)>.id|src&skip=true), [example 4](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|featuredimage<skip(if:$skip)>.id|src&skip=)):_

```
1. ?query=posts.id|title|featuredimage<include(if:$include)>.id|src&include=true
2. ?query=posts.id|title|featuredimage<include(if:$include)>.id|src&include=
3. ?query=posts.id|title|featuredimage<skip(if:$skip)>.id|src&skip=true
4. ?query=posts.id|title|featuredimage<skip(if:$skip)>.id|src&skip=
```

### Combining elements

Different elements can be combined. For instance: A fragment can contain nested paths, variables, directives and other fragments:

_**In PoP** ([example](https://nextapi.getpop.org/api/graphql/?query=posts(limit:$limit).--postData|author.posts(limit:$limit).--postData&postData=id|title|--nestedPostData|date(format:$format)&nestedPostData=comments<include(if:$include)>.id|content&format=d/m/Y&include=true&limit=3)):_

```
/?query=posts(limit:$limit).--postData|author.posts(limit:$limit).--postData&postData=id|title|--nestedPostData|date(format:$format)&nestedPostData=comments<include(if:$include)>.id|content&format=d/m/Y&include=true&limit=3
```

### Operators and Nested fields



### Skip output if null
<!--
## Query examples

Field arguments:

- Order posts by title: [posts(order:title|asc)](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc).id|title|url|date)
- Search "template" and limit it to 3 results: [posts(searchfor:template,limit:3)](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3).id|title|url|date)
- Format a date: [posts.date(format:d/m/Y)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y))

Alias:

- [posts(order:title|asc)@orderedposts](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc)@orderedposts.id|title|url|date)
- [posts.date(format:d/m/Y)@formatteddate](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y)@formatteddate)

Bookmark:

- [posts.comments[comments].author.id|name,[comments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[comments].author.id|name,[comments].post.id|title)



Bookmark with alias:

- [posts.comments[@postcomments].author.id|name,[postcomments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[@postcomments].author.id|name,[postcomments].post.id|title)



Variables:

- [posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template)

Fragments:

- [posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title](https://nextapi.getpop.org/api/graphql/?query=posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title)


Directives:

- [posts.id|title|url<include(if:$include)>&variables[include]=true](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=true)
- [posts.id|title|url<include(if:$include)>&variables[include]=](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=)

-->

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

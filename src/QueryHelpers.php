<?php
namespace PoP\FieldQuery;

use PoP\QueryParsing\Facades\QueryParserFacade;

class QueryHelpers
{
    public static function listFieldArgsSymbolPositions(string $field): array
    {
        return [
            QueryUtils::findFirstSymbolPosition($field, QuerySyntax::SYMBOL_FIELDARGS_OPENING, [QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING),
            QueryUtils::findLastSymbolPosition($field, QuerySyntax::SYMBOL_FIELDARGS_CLOSING, [QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING),
        ];
    }

    public static function listFieldBookmarkSymbolPositions(string $field): array
    {
        return [
            QueryUtils::findFirstSymbolPosition($field, QuerySyntax::SYMBOL_BOOKMARK_OPENING, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]),
            QueryUtils::findLastSymbolPosition($field, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]),
        ];
    }

    public static function findFieldAliasSymbolPosition(string $field)
    {
        return QueryUtils::findFirstSymbolPosition($field, QuerySyntax::SYMBOL_FIELDALIAS_PREFIX, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]);
    }

    public static function findSkipOutputIfNullSymbolPosition(string $field)
    {
        return QueryUtils::findFirstSymbolPosition($field, QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_BOOKMARK_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]);
    }

    public static function listFieldDirectivesSymbolPositions(string $field): array
    {
        return [
            QueryUtils::findFirstSymbolPosition($field, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING, QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_CLOSING),
            QueryUtils::findLastSymbolPosition($field, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING, QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_CLOSING),
        ];
    }

    public static function getEmptyFieldArgs(): string
    {
        return QuerySyntax::SYMBOL_FIELDARGS_OPENING.QuerySyntax::SYMBOL_FIELDARGS_CLOSING;
    }

    public static function getFieldArgElements(?string $fieldArgsAsString): array
    {
        if ($fieldArgsAsString) {
            // Remove the opening and closing brackets
            $fieldArgsAsString = substr($fieldArgsAsString, strlen(QuerySyntax::SYMBOL_FIELDARGS_OPENING), strlen($fieldArgsAsString)-strlen(QuerySyntax::SYMBOL_FIELDARGS_OPENING)-strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING));
            // Remove the white spaces before and after
            if ($fieldArgsAsString = trim($fieldArgsAsString)) {
                $queryParser = QueryParserFacade::getInstance();
                return $queryParser->splitElements($fieldArgsAsString, QuerySyntax::SYMBOL_FIELDARGS_ARGSEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
            }
        }
        return [];
    }
}
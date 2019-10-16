<?php
namespace PoP\FieldQuery\Query;

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
}
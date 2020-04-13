<?php
namespace PoP\FieldQuery;
use PoP\ComponentModel\Feedback\Tokens;
use PoP\QueryParsing\QueryParserOptions;
use PoP\QueryParsing\Facades\QueryParserFacade;

class QueryUtils
{
    public static function findFirstSymbolPosition(string $haystack, string $needle, $skipFromChars = '', $skipUntilChars = '')
    {
        // Edge case: If the string starts with the symbol, then the array count of splitting the elements will be 1
        if (substr($haystack, 0, strlen($needle)) == $needle) {
            return 0;
        }
        // Split on that searching element: If it appears within the string, it will produce an array with exactly 2 elements (since using option "ONLY_FIRST_OCCURRENCE")
        // The length of the first element equals the position of that symbol
        $fieldQueryInterpreter = QueryParserFacade::getInstance();
        $options = [
            QueryParserOptions::ONLY_FIRST_OCCURRENCE => true,
        ];
        $symbolElems = $fieldQueryInterpreter->splitElements($haystack, $needle, $skipFromChars, $skipUntilChars, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING, $options);
        if (count($symbolElems) == 2) {
            return strlen($symbolElems[0]);
        }
        // Edge case: If the string finishes with the symbol, then the array count of splitting the elements will be 1
        if (substr($haystack, -1 * strlen($needle)) == $needle) {
            return strlen($haystack) - strlen($needle);
        }

        return false;
    }

    public static function findLastSymbolPosition(string $haystack, string $needle, $skipFromChars = '', $skipUntilChars = '')
    {
        // Edge case: If the string finishes with the symbol, then the array count of splitting the elements will be 1
        if (substr($haystack, -1 * strlen($needle)) == $needle) {
            return strlen($haystack) - strlen($needle);
        }
        // Split on that searching element: If it appears within the string, it will produce an array with exactly 2 elements (since using option "ONLY_FIRST_OCCURRENCE")
        // The length of the first element equals the position of that symbol
        $fieldQueryInterpreter = QueryParserFacade::getInstance();
        $options = [
            QueryParserOptions::START_FROM_END => true,
            QueryParserOptions::ONLY_FIRST_OCCURRENCE => true,
        ];
        $symbolElems = $fieldQueryInterpreter->splitElements($haystack, $needle, $skipFromChars, $skipUntilChars, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING, $options);
        if (count($symbolElems) == 2) {
            return strlen($symbolElems[0]);
        }
        // Edge case: If the string starts with the symbol, then the array count of splitting the elements will be 1
        if (substr($haystack, 0, strlen($needle)) == $needle) {
            return 0;
        }

        return false;
    }

    public static function convertLocationArrayIntoString(int $line, int $column): string
    {
        return sprintf(
            '%s%s%s',
            $line,
            Tokens::LOCATION_ITEMS_SEPARATOR,
            $column
        );
    }

    public static function convertLocationStringIntoArray(string $location): array
    {
        $locationParts = explode(Tokens::LOCATION_ITEMS_SEPARATOR, $location);
        return [
            'line' => $locationParts[0],
            'column' => $locationParts[1],
        ];
    }
}

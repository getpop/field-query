<?php
namespace PoP\FieldQuery;

use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;

class FieldQueryUtils
{
    public static function isAnyFieldArgumentValueAField(array $fieldArgValues): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        return self::isAnyFieldArgumentValueASomething(
            $fieldArgValues,
            [$fieldQueryInterpreter, 'isFieldArgumentValueAField']
        );
    }

    public static function isAnyFieldArgumentValueAFieldOrVariable(array $fieldArgValues): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        return self::isAnyFieldArgumentValueASomething(
            $fieldArgValues,
            function($fieldArgValue) use($fieldQueryInterpreter) {
                return
                    // Is it a field?
                    $fieldQueryInterpreter->isFieldArgumentValueAField($fieldArgValue) ||
                    // Is it a variable?
                    $fieldQueryInterpreter->isFieldArgumentValueAVariable($fieldArgValue);
            }
        );
    }

    /**
     * Indicate if the fieldArgValue is whatever is needed to know, executed against a $callback function
     *
     * @param array $fieldArgValues
     * @param callback $callback
     * @return boolean
     */
    public static function isAnyFieldArgumentValueASomething(array $fieldArgValues, callable $callback): bool
    {
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        $isOrContainsAField = array_map(
            function($fieldArgValue) use($fieldQueryInterpreter, $callback) {
                // Either the value is a field, or it is an array of fields
                if (is_array($fieldArgValue)) {
                    return self::isAnyFieldArgumentValueASomething((array)$fieldArgValue, $callback);
                }
                return $callback($fieldArgValue);
            },
            $fieldArgValues
        );
        return (in_array(true, $isOrContainsAField));
    }
}
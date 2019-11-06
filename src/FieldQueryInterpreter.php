<?php
namespace PoP\FieldQuery;
use PoP\Translation\TranslationAPIInterface;
use PoP\QueryParsing\QueryParserInterface;

class FieldQueryInterpreter implements FieldQueryInterpreterInterface
{
    // Cache the output from functions
    private $fieldNamesCache = [];
    private $fieldArgsCache = [];
    private $skipOutputIfNullCache = [];
    private $fieldAliasesCache = [];
    private $fieldDirectivesCache = [];
    private $directivesCache = [];
    private $extractedFieldDirectivesCache = [];
    private $fieldOutputKeysCache = [];

    // Cache vars to take from the request
    private $variablesFromRequestCache;

    // Services
    protected $translationAPI;
    protected $feedbackMessageStore;
    protected $queryParser;

    public function __construct(
        TranslationAPIInterface $translationAPI,
        FeedbackMessageStoreInterface $feedbackMessageStore,
        QueryParserInterface $queryParser
    ) {
        $this->translationAPI = $translationAPI;
        $this->feedbackMessageStore = $feedbackMessageStore;
        $this->queryParser = $queryParser;
    }

    public function getFieldName(string $field): string
    {
        if (!isset($this->fieldNamesCache[$field])) {
            $this->fieldNamesCache[$field] = $this->doGetFieldName($field);
        }
        return $this->fieldNamesCache[$field];
    }

    protected function doGetFieldName(string $field): string
    {
        // Successively search for the position of some edge symbol
        // Everything before "(" (for the fieldArgs)
        list($pos) = QueryHelpers::listFieldArgsSymbolPositions($field);
        // Everything before "@" (for the alias)
        if ($pos === false) {
            $pos = QueryHelpers::findFieldAliasSymbolPosition($field);
        }
        // Everything before "?" (for "skip output if null")
        if ($pos === false) {
            $pos = QueryHelpers::findSkipOutputIfNullSymbolPosition($field);
        }
        // Everything before "<" (for the field directive)
        if ($pos === false) {
            list($pos) = QueryHelpers::listFieldDirectivesSymbolPositions($field);
        }
        // If the field name is missing, show an error
        if ($pos === 0) {
            $this->feedbackMessageStore->addQueryError(sprintf(
                $this->translationAPI->__('Name in \'%s\' is missing', 'pop-component-model'),
                $field
            ));
            return '';
        }
        // Extract the query until the found position
        if ($pos !== false) {
            return substr($field, 0, $pos);
        }
        // No fieldArgs, no alias => The field is the fieldName
        return $field;
    }

    public function getVariablesFromRequest(): array
    {
        if (is_null($this->variablesFromRequestCache)) {
            $this->variablesFromRequestCache = $this->doGetVariablesFromRequest();
        }
        return $this->variablesFromRequestCache;
    }

    protected function doGetVariablesFromRequest(): array
    {
        return array_merge(
            $_REQUEST,
            $_REQUEST['variables'] ?? []
        );
    }

    public function getFieldArgs(string $field): ?string
    {
        if (!isset($this->fieldArgsCache[$field])) {
            $this->fieldArgsCache[$field] = $this->doGetFieldArgs($field);
        }
        return $this->fieldArgsCache[$field];
    }

    protected function doGetFieldArgs(string $field): ?string
    {
        // We check that the format is "$fieldName($prop1;$prop2;...;$propN)"
        // or also with [] at the end: "$fieldName($prop1;$prop2;...;$propN)[somename]"
        list(
            $fieldArgsOpeningSymbolPos,
            $fieldArgsClosingSymbolPos
        ) = QueryHelpers::listFieldArgsSymbolPositions($field);

        // If there are no "(" and ")" then there are no field args
        if ($fieldArgsClosingSymbolPos === false && $fieldArgsOpeningSymbolPos === false) {
            return null;
        }
        // If there is only one of them, it's a query error, so discard the query bit
        if (($fieldArgsClosingSymbolPos === false && $fieldArgsOpeningSymbolPos !== false) || ($fieldArgsClosingSymbolPos !== false && $fieldArgsOpeningSymbolPos === false)) {
            $this->feedbackMessageStore->addQueryError(sprintf(
                $this->translationAPI->__('Arguments \'%s\' must start with symbol \'%s\' and end with symbol \'%s\'', 'pop-component-model'),
                $field,
                QuerySyntax::SYMBOL_FIELDARGS_OPENING,
                QuerySyntax::SYMBOL_FIELDARGS_CLOSING
            ));
            return null;
        }

        // We have field args. Extract them, including the brackets
        return substr($field, $fieldArgsOpeningSymbolPos, $fieldArgsClosingSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING)-$fieldArgsOpeningSymbolPos);
    }

    public function isSkipOuputIfNullField(string $field): bool
    {
        if (!isset($this->skipOutputIfNullCache[$field])) {
            $this->skipOutputIfNullCache[$field] = $this->doIsSkipOuputIfNullField($field);
        }
        return $this->skipOutputIfNullCache[$field];
    }

    protected function doIsSkipOuputIfNullField(string $field): bool
    {
        return QueryHelpers::findSkipOutputIfNullSymbolPosition($field) !== false;
    }

    public function removeSkipOuputIfNullFromField(string $field): string
    {
        $pos = QueryHelpers::findSkipOutputIfNullSymbolPosition($field);
        if ($pos !== false) {
            // Replace the "?" with nothing
            $field = str_replace(
                QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL,
                '',
                $field
            );
        }
        return $field;
    }

    /**
     * Replace the fieldArgs in the field
     *
     * @param string $field
     * @param array $fieldArgs
     * @return string
     */
    protected function replaceFieldArgs(string $field, array $fieldArgs = []): string
    {
        // Return a new field, replacing its fieldArgs (if any) with the provided ones
        // Used when validating a field and removing the fieldArgs that threw a warning
        list(
            $fieldArgsOpeningSymbolPos,
            $fieldArgsClosingSymbolPos
        ) = QueryHelpers::listFieldArgsSymbolPositions($field);

        // If it currently has fieldArgs, append the fieldArgs after the fieldName
        if ($fieldArgsOpeningSymbolPos !== false && $fieldArgsClosingSymbolPos !== false) {
            $fieldName = $this->getFieldName($field);
            return substr($field, 0, $fieldArgsOpeningSymbolPos).$this->getFieldArgsAsString($fieldArgs).substr($field, $fieldArgsClosingSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING));
        }

        // Otherwise there are none. Then add the fieldArgs between the fieldName and whatever may come after (alias, directives, or nothing)
        $fieldName = $this->getFieldName($field);
        return $fieldName.$this->getFieldArgsAsString($fieldArgs).substr($field, strlen($fieldName));
    }

    public function isFieldArgumentValueAField($fieldArgValue): bool
    {
        // If the result fieldArgValue is a string (i.e. not numeric), and it has brackets (...),
        // then it is a field
        return
            !empty($fieldArgValue) &&
            is_string($fieldArgValue) &&
            substr($fieldArgValue, -1*strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING)) == QuerySyntax::SYMBOL_FIELDARGS_CLOSING &&
            // Please notice: if position is 0 (i.e. for a string "(something)") then it's not a field, since the fieldName is missing
            // Then it's ok asking for strpos: either `false` or `0` must both fail
            strpos($fieldArgValue, QuerySyntax::SYMBOL_FIELDARGS_OPENING);
    }

    public function isFieldArgumentValueAVariable($fieldArgValue): bool
    {
        // If it starts with "$", it is a variable
        return is_string($fieldArgValue) && substr($fieldArgValue, 0, strlen(QuerySyntax::SYMBOL_VARIABLE_PREFIX)) == QuerySyntax::SYMBOL_VARIABLE_PREFIX;
    }

    public function isFieldArgumentValueAnArrayRepresentedAsString($fieldArgValue): bool
    {
        // If it starts with "[" and finishes with "]"
        return is_string($fieldArgValue) && substr($fieldArgValue, 0, strlen(QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING)) == QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING && substr($fieldArgValue, -1*strlen(QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING)) == QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING;
    }

    public function createFieldArgValueAsFieldFromFieldName(string $fieldName): string
    {
        return $fieldName.QueryHelpers::getEmptyFieldArgs();
    }

    public function getFieldAlias(string $field): ?string
    {
        if (!isset($this->fieldAliasesCache[$field])) {
            $this->fieldAliasesCache[$field] = $this->doGetFieldAlias($field);
        }
        return $this->fieldAliasesCache[$field];
    }

    protected function doGetFieldAlias(string $field): ?string
    {
        $aliasSymbolPos = QueryHelpers::findFieldAliasSymbolPosition($field);
        if ($aliasSymbolPos !== false) {
            if ($aliasSymbolPos === 0) {
                // Only there is the alias, nothing to alias to
                $this->feedbackMessageStore->addQueryError(sprintf(
                    $this->translationAPI->__('The field to be aliased in \'%s\' is missing', 'pop-component-model'),
                    $field
                ));
                return null;
            } elseif ($aliasSymbolPos === strlen($field)-1) {
                // Only the "@" was added, but the alias is missing
                $this->feedbackMessageStore->addQueryError(sprintf(
                    $this->translationAPI->__('Alias in \'%s\' is missing', 'pop-component-model'),
                    $field
                ));
                return null;
            }

            // Extract the alias, without the "@" symbol
            $alias = substr($field, $aliasSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDALIAS_PREFIX));

            // If there is a "]", "?" or "<" after the alias, remove the string from then on
            // Everything before "]" (for if the alias is inside the bookmark)
            list (
                $bookmarkOpeningSymbolPos,
                $pos
            ) = QueryHelpers::listFieldBookmarkSymbolPositions($alias);
            // Everything before "?" (for "skip output if null")
            if ($pos === false) {
                $pos = QueryHelpers::findSkipOutputIfNullSymbolPosition($alias);
            }
            // Everything before "<" (for the field directive)
            if ($pos === false) {
                list($pos) = QueryHelpers::listFieldDirectivesSymbolPositions($alias);
            }
            if ($pos !== false) {
                $alias = substr($alias, 0, $pos);
            }
            return $alias;
        }
        return null;
    }

    public function getFieldDirectives(string $field): ?string
    {
        if (!isset($this->fieldDirectivesCache[$field])) {
            $this->fieldDirectivesCache[$field] = $this->doGetFieldDirectives($field);
        }
        return $this->fieldDirectivesCache[$field];
    }

    protected function doGetFieldDirectives(string $field): ?string
    {
        list(
            $fieldDirectivesOpeningSymbolPos,
            $fieldDirectivesClosingSymbolPos
        ) = QueryHelpers::listFieldDirectivesSymbolPositions($field);

        // If there are no "<" and "." then there is no directive
        if ($fieldDirectivesClosingSymbolPos === false && $fieldDirectivesOpeningSymbolPos === false) {
            return null;
        }
        // If there is only one of them, it's a query error, so discard the query bit
        if (($fieldDirectivesClosingSymbolPos === false && $fieldDirectivesOpeningSymbolPos !== false) || ($fieldDirectivesClosingSymbolPos !== false && $fieldDirectivesOpeningSymbolPos === false)) {
            $this->feedbackMessageStore->addQueryError(sprintf(
                $this->translationAPI->__('Directive \'%s\' must start with symbol \'%s\' and end with symbol \'%s\'', 'pop-component-model'),
                $field,
                QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING
            ));
            return null;
        }

        // We have a field directive. Extract it
        $fieldDirectiveOpeningSymbolStrPos = $fieldDirectivesOpeningSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING);
        $fieldDirectiveClosingStrPos = $fieldDirectivesClosingSymbolPos - $fieldDirectiveOpeningSymbolStrPos;
        return substr($field, $fieldDirectiveOpeningSymbolStrPos, $fieldDirectiveClosingStrPos);
    }

    public function getDirectives(string $field): array
    {
        if (!isset($this->directivesCache[$field])) {
            $this->directivesCache[$field] = $this->doGetDirectives($field);
        }
        return $this->directivesCache[$field];
    }

    protected function doGetDirectives(string $field): array
    {
        $fieldDirectives = $this->getFieldDirectives($field);
        if (is_null($fieldDirectives)) {
            return [];
        }
        return $this->extractFieldDirectives($fieldDirectives);
    }

    public function extractFieldDirectives(string $fieldDirectives): array
    {
        if (!isset($this->extractedFieldDirectivesCache[$fieldDirectives])) {
            $this->extractedFieldDirectivesCache[$fieldDirectives] = $this->doExtractFieldDirectives($fieldDirectives);
        }
        return $this->extractedFieldDirectivesCache[$fieldDirectives];
    }

    protected function doExtractFieldDirectives(string $fieldDirectives): array
    {
        if (!$fieldDirectives) {
            return [];
        }
        return array_map(
            [$this, 'listFieldDirective'],
            $this->queryParser->splitElements($fieldDirectives, QuerySyntax::SYMBOL_FIELDDIRECTIVE_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_BOOKMARK_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING)
        );
    }

    public function composeFieldDirectives(array $fieldDirectives): string
    {
        return implode(QuerySyntax::SYMBOL_FIELDDIRECTIVE_SEPARATOR, $fieldDirectives);
    }

    public function convertDirectiveToFieldDirective(array $fieldDirective): string
    {
        $directiveArgs = $this->getDirectiveArgs($fieldDirective) ?? '';
        return $this->getDirectiveName($fieldDirective).$directiveArgs;
    }

    public function listFieldDirective(string $fieldDirective): array
    {
        // Each item is an array of 2 elements: 0 => name, 1 => args
        return [
            $this->getFieldName($fieldDirective),
            $this->getFieldArgs($fieldDirective),
        ];
    }

    public function getFieldDirectiveName(string $fieldDirective): string
    {
        return $this->getFieldName($fieldDirective);
    }

    public function getFieldDirectiveArgs(string $fieldDirective): ?string
    {
        return $this->getFieldArgs($fieldDirective);
    }

    public function getFieldDirective(string $directiveName, array $directiveArgs = []): string
    {
        return $this->getField($directiveName, $directiveArgs);
    }

    public function getDirectiveName(array $directive): string
    {
        return $directive[0];
    }

    public function getDirectiveArgs(array $directive): ?string
    {
        return $directive[1];
    }

    public function getDirectiveOutputKey(string $fieldDirective): string
    {
        return $this->getFieldOutputKey($fieldDirective);
    }

    public function getFieldOutputKey(string $field): string
    {
        if (!isset($this->fieldOutputKeysCache[$field])) {
            $this->fieldOutputKeysCache[$field] = $this->doGetFieldOutputKey($field);
        }
        return $this->fieldOutputKeysCache[$field];
    }

    protected function doGetFieldOutputKey(string $field): string
    {
        // If there is an alias, use this to represent the field
        if ($fieldAlias = $this->getFieldAlias($field)) {
            return $fieldAlias;
        }
        // Otherwise, use fieldName+fieldArgs
        return $this->getFieldName($field).$this->getFieldArgs($field);
    }

    public function listField(string $field): array
    {
        return [
            $this->getFieldName($field),
            $this->getFieldArgs($field),
            $this->getFieldAlias($field),
            $this->isSkipOuputIfNullField($field),
            $this->getDirectives($field),
        ];
    }

    public function getField(string $fieldName, array $fieldArgs = [], ?string $fieldAlias = null, ?bool $skipOutputIfNull = false, ?array $fieldDirectives = []): string
    {
        return
            $fieldName.
            $this->getFieldArgsAsString($fieldArgs).
            $this->getFieldAliasAsString($fieldAlias).
            $this->getFieldSkipOutputIfNullAsString($skipOutputIfNull).
            $this->getFieldDirectivesAsString($fieldDirectives);
    }

    public function composeField(string $fieldName, string $fieldArgs = '', string $fieldAlias = '', string $skipOutputIfNull = '', string $fieldDirectives = ''): string
    {
        return $fieldName.$fieldArgs.$fieldAlias.$skipOutputIfNull.$fieldDirectives;
    }

    protected function getFieldArgsAsString(array $fieldArgs = []): string
    {
        if (!$fieldArgs) {
            return '';
        }
        $elems = [];
        foreach ($fieldArgs as $fieldArgKey => $fieldArgValue) {
            // Convert from array to its representation of array in a string
            if (is_array($fieldArgValue)) {
                $fieldArgValue = $this->getArrayAsStringForQuery($fieldArgValue);
            }
            $elems[] = $fieldArgKey.QuerySyntax::SYMBOL_FIELDARGS_ARGKEYVALUESEPARATOR.$fieldArgValue;
        }
        return QuerySyntax::SYMBOL_FIELDARGS_OPENING.implode(QuerySyntax::SYMBOL_FIELDARGS_ARGSEPARATOR, $elems).QuerySyntax::SYMBOL_FIELDARGS_CLOSING;
    }

    protected function getArrayAsStringForQuery(array $fieldArgValue): string
    {
        // Iterate through all the elements of the array and, if they are an array themselves, call this function recursively
        $elems = [];
        foreach ($fieldArgValue as $key => $value) {
            // Add the keyValueDelimiter
            if (is_array($value)) {
                $elems[] = $key.QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_KEYVALUEDELIMITER.$this->getArrayAsStringForQuery($value);
            } else {
                $elems[] = $key.QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_KEYVALUEDELIMITER.$value;
            }
        }
        return
            QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING.
            implode(
                QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_SEPARATOR,
                $elems
            )
            .QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING;
    }

    protected function getFieldAliasAsString(?string $fieldAlias = null): string
    {
        if (!$fieldAlias) {
            return '';
        }
        return QuerySyntax::SYMBOL_FIELDALIAS_PREFIX.$fieldAlias;
    }

    protected function getFieldSkipOutputIfNullAsString(?bool $skipOutputIfNull = false): string
    {
        if (!$skipOutputIfNull) {
            return '';
        }
        return QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL;
    }

    public function getFieldDirectivesAsString(?array $fieldDirectives = []): string
    {
        if (!$fieldDirectives) {
            return '';
        }
        return
            QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING.
            implode(QuerySyntax::SYMBOL_FIELDDIRECTIVE_SEPARATOR, array_map(
                function($fieldDirective) {
                    return $this->composeField(
                        $fieldDirective[0],
                        $fieldDirective[1]
                    );
                },
                $fieldDirectives
            )).
            QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING;
    }
}

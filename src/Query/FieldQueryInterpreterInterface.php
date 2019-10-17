<?php
namespace PoP\FieldQuery\Query;

interface FieldQueryInterpreterInterface
{
    public function getFieldName(string $field): string;
    public function getFieldArgs(string $field): ?string;
    public function isSkipOuputIfNullField(string $field): bool;
    public function removeSkipOuputIfNullFromField(string $field): string;
    public function isFieldArgumentValueAField($fieldArgValue): bool;
    public function isFieldArgumentValueAVariable($fieldArgValue): bool;
    public function isFieldArgumentValueAnArray($fieldArgValue): bool;
    public function createFieldArgValueAsFieldFromFieldName(string $fieldName): string;
    public function getFieldAlias(string $field): ?string;
    public function getFieldDirectives(string $field): ?string;
    public function getDirectives(string $field): array;
    public function extractFieldDirectives(string $fieldDirectives): array;
    public function composeFieldDirectives(array $fieldDirectives): string;
    public function convertDirectiveToFieldDirective(array $fieldDirective): string;
    public function listFieldDirective(string $fieldDirective): array;
    public function getFieldDirectiveName(string $fieldDirective): string;
    public function getFieldDirectiveArgs(string $fieldDirective): ?string;
    public function getFieldDirective(string $directiveName, array $directiveArgs = []): string;
    public function getDirectiveName(array $directive): string;
    public function getDirectiveArgs(array $directive): ?string;
    public function getFieldOutputKey(string $field): string;
    public function listField(string $field): array;
    public function getField(string $fieldName, array $fieldArgs = [], ?string $fieldAlias = null, ?bool $skipOutputIfNull = false, ?array $fieldDirectives = []): string;
    public function composeField(string $fieldName, string $fieldArgs = '', string $fieldAlias = '', string $skipOutputIfNull = '', string $fieldDirectives = ''): string;
    public function getFieldDirectiveAsString(array $fieldDirectives): string;
}

<?php
namespace PoP\FieldQuery;

interface FieldQueryInterpreterInterface
{
    public function getFieldName(string $field): string;
    public function getFieldArgs(string $field): ?string;
    public function isSkipOuputIfNullField(string $field): bool;
    public function removeSkipOuputIfNullFromField(string $field): string;
    public function isFieldArgumentValueAField($fieldArgValue): bool;
    public function isFieldArgumentValueAVariable($fieldArgValue): bool;
    public function isFieldArgumentValueAnArrayRepresentedAsString($fieldArgValue): bool;
    public function createFieldArgValueAsFieldFromFieldName(string $fieldName): string;
    public function getFieldAlias(string $field): ?string;
    public function getFieldDirectives(string $field, bool $includeSyntaxDelimiters): ?string;
    public function getDirectives(string $field): array;
    public function extractFieldDirectives(string $fieldDirectives): array;
    public function composeFieldDirectives(array $fieldDirectives): string;
    public function convertDirectiveToFieldDirective(array $fieldDirective): string;
    public function listFieldDirective(string $fieldDirective): array;
    public function getFieldDirectiveName(string $fieldDirective): string;
    public function getFieldDirectiveArgs(string $fieldDirective): ?string;
    public function getFieldDirectiveNestedDirectives(string $fieldDirective, $includeSyntaxDelimiters = false): ?string;
    public function getFieldDirective(string $directiveName, array $directiveArgs = []): string;
    public function getDirectiveName(array $directive): string;
    public function getDirectiveArgs(array $directive): ?string;
    public function getDirectiveNestedDirectives(array $directive): ?string;
    public function getFieldOutputKey(string $field): string;
    public function getDirectiveOutputKey(string $fieldDirective): string;
    public function listField(string $field): array;
    public function getField(string $fieldName, array $fieldArgs = [], ?string $fieldAlias = null, ?bool $skipOutputIfNull = false, ?array $fieldDirectives = []): string;
    public function composeField(string $fieldName, ?string $fieldArgs = '', ?string $fieldAlias = '', ?string $skipOutputIfNull = '', ?string $fieldDirectives = ''): string;
    public function composeFieldDirective(string $directiveName, ?string $directiveArgs = '', ?string $directiveNestedDirectives = ''): string;
    public function getFieldDirectivesAsString(array $fieldDirectives): string;
    public function getVariablesFromRequest(): array;
    public function getArrayAsStringForQuery(array $fieldArgValue): string;
}

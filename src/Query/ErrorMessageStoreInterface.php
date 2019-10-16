<?php
namespace PoP\FieldQuery\Query;

interface ErrorMessageStoreInterface
{
    function addQueryError(string $error);
    function getQueryErrors(): array;
}

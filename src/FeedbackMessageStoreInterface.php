<?php
namespace PoP\FieldQuery;

interface FeedbackMessageStoreInterface
{
    /**
     * $location is optional. If provided, it is an array with keys "line" and "column"
     *
     * @param string $error
     * @param array|null $location array with keys "line" and "column"
     * @return void
     */
    function addQueryError(string $error, ?array $location = null);
    function getQueryErrors(): array;
}

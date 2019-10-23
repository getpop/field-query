<?php
namespace PoP\FieldQuery;

interface FeedbackMessageStoreInterface
{
    function addQueryError(string $error);
    function getQueryErrors(): array;
}

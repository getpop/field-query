<?php
namespace PoP\FieldQuery\Query;

interface FeedbackMessageStoreInterface
{
    function addQueryError(string $error);
    function getQueryErrors(): array;
}

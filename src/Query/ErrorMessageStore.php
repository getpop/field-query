<?php
namespace PoP\FieldQuery\Query;

class ErrorMessageStore implements ErrorMessageStoreInterface
{
    protected $queryErrors = [];

    public function addQueryError(string $error)
    {
        $this->queryErrors[] = $error;
    }
    public function getQueryErrors(): array
    {
        return array_unique($this->queryErrors);
    }
}

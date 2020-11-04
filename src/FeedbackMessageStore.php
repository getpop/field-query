<?php

declare(strict_types=1);

namespace PoP\FieldQuery;

class FeedbackMessageStore implements FeedbackMessageStoreInterface
{
    /**
     * @var array<string, array>
     */
    protected array $queryErrors = [];

    /**
     * $extensions is optional. It is used by GraphQL to pass the location with "line" and "column" (as a string)
     *
     * @param string $error
     * @param mixed[] $extensions Adding extra information (eg: location error for GraphQL)
     * @return void
     */
    public function addQueryError(string $error, array $extensions = []): void
    {
        $this->queryErrors[$error] = $extensions;
    }
    /**
     * @return array<string, array>
     */
    public function getQueryErrors(): array
    {
        // return array_unique($this->queryErrors);
        return $this->queryErrors;
    }
}

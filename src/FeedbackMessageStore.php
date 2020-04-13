<?php

declare(strict_types=1);

namespace PoP\FieldQuery;

use PoP\ComponentModel\Feedback\Tokens;

class FeedbackMessageStore implements FeedbackMessageStoreInterface
{
    protected $queryErrors = [];

    /**
     * $location is optional. If provided, it is an array with keys "line" and "column"
     *
     * @param string $error
     * @param array|null $location array with keys "line" and "column"
     * @return void
     */
    public function addQueryError(string $error, ?array $location = null)
    {
        if ($location) {
            $key = QueryUtils::convertLocationArrayIntoString($location['line'], $location['column']);
            $this->queryErrors[$key] = $error;
        } else {
            $this->queryErrors[] = $error;
        }
    }
    public function getQueryErrors(): array
    {
        // return array_unique($this->queryErrors);
        return $this->queryErrors;
    }
}

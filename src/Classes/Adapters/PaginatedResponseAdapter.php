<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Illuminate\Support\Collection;

/**
 * @template T
 */
class PaginatedResponseAdapter extends ResponseAdapter
{
    public function __construct(Collection $response, int $statusCode = 200)
    {
        parent::__construct($response, $statusCode);
    }

    public function current(): mixed
    {
        return $this->response['data'][$this->position];
    }

    public function valid(): bool
    {
        return isset($this->response['data'][$this->position]);
    }
}
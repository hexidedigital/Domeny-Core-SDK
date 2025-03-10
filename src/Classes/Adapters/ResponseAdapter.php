<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use Illuminate\Support\Collection;
use Iterator;
use Psr\Http\Message\ResponseInterface;

class ResponseAdapter implements Iterator
{
    private const SUCCESS_CODES = [200];
    public Collection $response;
    public int $statusCode;
    protected int $position = 0;

    public function __construct(
        Collection $response,
        int $statusCode = 200,
    ) {
        $this->response = $response;
        $this->statusCode = $statusCode;
        $this->position = 0;
    }

    public static function fromResponse(ResponseInterface $response, ?callable $map = null): self
    {
        if (! in_array($response->getStatusCode(), self::SUCCESS_CODES)) {
            return new self(collect([]), $response->getStatusCode());
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!empty($map) && is_callable($map)) {
            $data = $map($data);
        }

        return new static(collect($data), $response->getStatusCode());
    }

    public function current(): mixed
    {
        return $this->response[$this->position];
    }

    public function valid(): bool
    {
        return isset($this->response[$this->position]);
    }


    public function next(): void
    {
        ++$this->position;
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
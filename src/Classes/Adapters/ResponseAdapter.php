<?php

namespace Hexidedigital\DomenyCoreSdk\Classes\Adapters;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use Iterator;
use Psr\Http\Message\ResponseInterface;

/**
 * @template T
 */
class ResponseAdapter implements Iterator
{
    protected const SUCCESS_CODES = [200];

    /** @var Collection<T>|T|null */
    public Collection|BaseAdapter|null $response;
    public int $statusCode;
    protected int $position = 0;

    /**
     * @param Collection<T>|T|null $response
     */
    public function __construct(
        Collection|BaseAdapter|null $response,
        int $statusCode = 200,
    ) {
        $this->response = $response;
        $this->statusCode = $statusCode;
        $this->position = 0;
    }

    public function isSuccessful(): bool
    {
        return in_array($this->statusCode, self::SUCCESS_CODES);
    }

    /**
     * @template U
     * @param callable(array):U|null $map
     * @return ResponseAdapter<U>
     */
    public static function fromResponse(ResponseInterface $response, ?callable $map = null): static
    {
        if (! in_array($response->getStatusCode(), static::SUCCESS_CODES)) {
            $data = json_decode($response->getBody()->getContents(), true);
            return new static($data, $response->getStatusCode());
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!empty($map) && is_callable($map)) {
            $data = $map($data);
        }

        return new static($data, $response->getStatusCode());
    }

    public static function fromError(ClientException $exception): static
    {
        return new static(
            response: collect(json_decode($exception->getResponse()->getBody()->getContents(), true)),
            statusCode: $exception->getCode()
        );
    }

    /**
     * @return T
     */
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
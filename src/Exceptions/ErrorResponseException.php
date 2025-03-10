<?php

namespace Hexidedigital\DomenyCoreSdk\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class ErrorResponseException extends Exception
{
    public ResponseInterface $response;
    public array $data;
    public int $statusCode;

    public function __construct(ResponseInterface $response)
    {
        $message = $response->getReasonPhrase();
        parent::__construct($message, 503, null);

        $this->response = $response;
        try {
            $this->data = json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            report($e);
            $this->data = [];
        }
        $this->statusCode = $response->getStatusCode();
    }
}
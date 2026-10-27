<?php

namespace App\Exceptions;

class HttpException extends \Exception
{
    public function __construct(
        public int $statusCode,
        public string $message,
        public array $details = []
    ) {
        parent::__construct($message);
    }
}

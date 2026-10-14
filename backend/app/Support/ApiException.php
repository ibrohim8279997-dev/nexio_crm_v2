<?php
namespace App\Support;

/** Mirrors the Worker's ApiError: a status + machine code + optional details. */
class ApiException extends \RuntimeException
{
    public function __construct(
        public int $status,
        string $message,
        public string $errorCode = 'error',
        public mixed $details = null
    ) {
        parent::__construct($message);
    }
}

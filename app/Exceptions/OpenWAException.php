<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción base para errores de OpenWA
 */
class OpenWAException extends Exception
{
    /**
     * Response HTTP de OpenWA
     */
    protected $response;

    public function __construct(string $message, ?array $response = null, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Obtener response de OpenWA
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}


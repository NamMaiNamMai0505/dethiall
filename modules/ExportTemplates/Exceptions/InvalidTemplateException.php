<?php

namespace Modules\ExportTemplates\Exceptions;

use RuntimeException;

class InvalidTemplateException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}

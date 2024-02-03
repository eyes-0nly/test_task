<?php 

declare(strict_types=1);

namespace App\Exception;

class EntityNotFoundException extends \Exception
{
    public static function create(string $message): self
    {
        return new static($message);
    }
}
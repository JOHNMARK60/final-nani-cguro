<?php

declare(strict_types=1);

namespace App\Validation;

final class Validator
{
    private array $errors = [];

    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        return $this;
    }

    public function email(array $data, string $field): self
    {
        if (!empty($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Please enter a valid email address.';
        }

        return $this;
    }

    public function strongPassword(string $password, string $field = 'password'): self
    {
        if (
            strlen($password) < 8
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[0-9]/', $password)
            || !preg_match('/[^A-Za-z0-9]/', $password)
        ) {
            $this->errors[$field] = 'Password must be at least 8 characters with uppercase, number, and special character.';
        }

        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }
}

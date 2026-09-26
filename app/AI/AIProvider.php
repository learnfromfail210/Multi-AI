<?php

declare(strict_types=1);

interface AIProvider
{
    public function getName(): string;

    public function chat(
        string $model,
        array $messages
    ): array;
}

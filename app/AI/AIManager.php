<?php

declare(strict_types=1);

final class AIManager
{
    /**
     * @var array<string, AIProvider>
     */
    private array $providers = [];

    public function register(
        string $name,
        AIProvider $provider
    ): void {
        $this->providers[$name] = $provider;
    }

    public function provider(
        string $name
    ): AIProvider {
        if (!isset($this->providers[$name])) {
            throw new RuntimeException(
                'AI provider is not configured.'
            );
        }

        return $this->providers[$name];
    }

    public function chat(
        string $providerName,
        string $model,
        array $messages
    ): array {
        return $this
            ->provider($providerName)
            ->chat($model, $messages);
    }

    public function providers(): array
    {
        return array_keys($this->providers);
    }
}

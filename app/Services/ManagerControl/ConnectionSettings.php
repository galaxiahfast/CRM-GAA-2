<?php

namespace App\Services\ManagerControl;

/**
 * Datos de conexión al legacy. La contraseña vive solo en memoria, nunca se serializa,
 * se imprime ni se incluye en toSafeArray().
 */
final class ConnectionSettings
{
    public function __construct(
        public readonly string $host,
        public readonly ?string $instance,
        public readonly ?int $port,
        public readonly string $database,
        public readonly ?string $username,
        private readonly ?string $password = null,
    ) {}

    public function server(): string
    {
        if ($this->port !== null) {
            return $this->host.','.$this->port;
        }

        return $this->host.($this->instance ? '\\'.$this->instance : '');
    }

    public function password(): ?string
    {
        return $this->password;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'motor' => 'Microsoft SQL Server',
            'servidor' => $this->host,
            'instancia' => $this->instance,
            'puerto' => $this->port,
            'base_de_datos' => $this->database,
            'usuario' => $this->username,
            'contraseña' => $this->password ? '(definida, oculta)' : '(no definida)',
        ];
    }

    public function __debugInfo(): array
    {
        return $this->toSafeArray();
    }

    public function __serialize(): array
    {
        throw new ManagerControlException('ConnectionSettings no se puede serializar.');
    }
}

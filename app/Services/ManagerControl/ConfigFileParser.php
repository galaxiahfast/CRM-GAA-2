<?php

namespace App\Services\ManagerControl;

/**
 * Lee (solo lectura) ManagerControl.exe.config y devuelve servidor/instancia/base de la cadena
 * de conexión. El usuario y la contraseña del archivo se DESCARTAN: jamás se devuelven ni se
 * registran. Las cadenas comentadas en el XML se ignoran.
 */
class ConfigFileParser
{
    private const MAX_BYTES = 1_048_576;

    /**
     * @return array{host: string, instance: ?string, port: ?int, database: ?string, file_has_credentials: bool}
     */
    public function parseFile(string $path, string $connectionName = 'DefaultConnectionString'): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new ManagerControlException('No se pudo leer el archivo de configuración de ManagerControl.');
        }
        if (filesize($path) > self::MAX_BYTES) {
            throw new ManagerControlException('El archivo de configuración es demasiado grande.');
        }

        return $this->parseXml((string) file_get_contents($path), $connectionName);
    }

    /**
     * @return array{host: string, instance: ?string, port: ?int, database: ?string, file_has_credentials: bool}
     */
    public function parseXml(string $xml, string $connectionName = 'DefaultConnectionString'): array
    {
        // Sin DTD/entidades (evita XXE) y sin red.
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            throw new ManagerControlException('El archivo de configuración contiene una DTD no permitida.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        if ($document === false) {
            throw new ManagerControlException('El archivo de configuración no es un XML válido.');
        }

        $raw = null;
        foreach ($document->connectionStrings->add ?? [] as $entry) {
            if ((string) $entry['name'] === $connectionName) {
                $raw = (string) $entry['connectionString'];
                break;
            }
        }
        if ($raw === null || trim($raw) === '') {
            throw new ManagerControlException("No existe la cadena de conexión «{$connectionName}» en el archivo.");
        }

        return $this->parseConnectionString($raw);
    }

    /**
     * @return array{host: string, instance: ?string, port: ?int, database: ?string, file_has_credentials: bool}
     */
    public function parseConnectionString(string $raw): array
    {
        $pairs = [];
        foreach (explode(';', $raw) as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $segment, 2);
            $pairs[strtolower(trim($key))] = trim($value);
        }

        $server = $pairs['data source'] ?? $pairs['server'] ?? $pairs['address'] ?? $pairs['addr'] ?? '';
        $server = preg_replace('/^(tcp|np|lpc):/i', '', $server) ?? '';
        if ($server === '') {
            throw new ManagerControlException('La cadena de conexión no indica el servidor.');
        }

        $port = null;
        $instance = null;
        if (str_contains($server, ',')) {
            [$server, $portText] = explode(',', $server, 2);
            $port = ctype_digit(trim($portText)) ? (int) trim($portText) : null;
        }
        if (str_contains($server, '\\')) {
            [$server, $instance] = explode('\\', $server, 2);
        }

        return [
            'host' => trim($server),
            'instance' => $instance !== null && trim($instance) !== '' ? trim($instance) : null,
            'port' => $port,
            'database' => $pairs['initial catalog'] ?? $pairs['database'] ?? null,
            // Solo se informa SI el archivo trae credenciales (nunca cuáles).
            'file_has_credentials' => isset($pairs['password']) || isset($pairs['pwd']) || isset($pairs['user id']) || isset($pairs['uid']),
        ];
    }
}

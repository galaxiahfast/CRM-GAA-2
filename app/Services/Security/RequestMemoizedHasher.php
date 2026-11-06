<?php

namespace App\Services\Security;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Hashing\HashManager;

/**
 * Evita repetir la misma verificación de contraseña dentro de una petición.
 *
 * Con la autenticación en dos pasos activa, Fortify valida la contraseña para decidir si pide el segundo
 * factor y vuelve a validarla al iniciar la sesión. bcrypt es lento a propósito (unos 200 ms con costo 12),
 * así que el inicio de sesión pagaba ese costo dos veces. Aquí se recuerda el resultado de comparar el mismo
 * texto con el mismo hash, de modo que la segunda comprobación sea inmediata. Las dos validaciones siguen
 * ocurriendo, con el mismo resultado, y el costo del hash no cambia.
 *
 * Solo se guarda una huella con clave aleatoria del par (contraseña, hash), nunca la contraseña, y se
 * conservan pocos resultados.
 */
final class RequestMemoizedHasher implements Hasher
{
    private const MAX_REMEMBERED = 8;

    /** @var array<string, bool> */
    private array $checked = [];

    private int $computed = 0;

    private readonly string $key;

    public function __construct(private readonly HashManager $manager)
    {
        $this->key = random_bytes(32);
    }

    public function info($hashedValue)
    {
        return $this->manager->info($hashedValue);
    }

    public function make(#[\SensitiveParameter] $value, array $options = [])
    {
        return $this->manager->make($value, $options);
    }

    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = [])
    {
        if (! is_string($value) || $value === '' || ! is_string($hashedValue) || $hashedValue === '') {
            return $this->manager->check($value, $hashedValue, $options);
        }

        $fingerprint = hash_hmac('sha256', $value."\0".$hashedValue."\0".json_encode($options), $this->key);

        if (array_key_exists($fingerprint, $this->checked)) {
            return $this->checked[$fingerprint];
        }

        $this->computed++;
        $result = $this->manager->check($value, $hashedValue, $options);

        if (count($this->checked) >= self::MAX_REMEMBERED) {
            array_shift($this->checked);
        }

        return $this->checked[$fingerprint] = $result;
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        return $this->manager->needsRehash($hashedValue, $options);
    }

    /**
     * Cantidad de verificaciones que realmente se calcularon (sin contar las repetidas).
     */
    public function computedChecks(): int
    {
        return $this->computed;
    }

    /**
     * El resto de la API del administrador de hashes (driver, extend, etc.) se conserva intacta.
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->manager->{$method}(...$parameters);
    }
}

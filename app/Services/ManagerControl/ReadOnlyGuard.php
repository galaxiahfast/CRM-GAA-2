<?php

namespace App\Services\ManagerControl;

/**
 * Última barrera contra escrituras: solo se acepta UNA sentencia que empiece con SELECT,
 * sin ";", sin SELECT…INTO, EXEC, ni palabras de escritura/DDL.
 */
final class ReadOnlyGuard
{
    private const FORBIDDEN = [
        'INSERT', 'UPDATE', 'DELETE', 'MERGE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'EXEC', 'EXECUTE',
        'GRANT', 'REVOKE', 'DENY', 'INTO', 'BULK', 'OPENROWSET', 'OPENQUERY', 'OPENDATASOURCE', 'WRITETEXT',
        'UPDATETEXT', 'BACKUP', 'RESTORE', 'SHUTDOWN', 'RECONFIGURE', 'KILL', 'DBCC', 'USE', 'DECLARE', 'SET',
        'BEGIN', 'COMMIT', 'ROLLBACK', 'WAITFOR',
    ];

    public static function assertReadOnly(string $sql): void
    {
        // Se quitan comentarios para que no oculten palabras prohibidas.
        $clean = trim(preg_replace(['/--[^\r\n]*/', '/\/\*.*?\*\//s'], ' ', $sql) ?? '');

        if (! preg_match('/^SELECT\b/i', $clean)) {
            throw new ReadOnlyViolation('Solo se permiten sentencias SELECT.');
        }
        if (str_contains($clean, ';')) {
            throw new ReadOnlyViolation('No se permiten varias sentencias.');
        }
        // El contenido de literales entre comillas simples no cuenta al buscar palabras prohibidas.
        $withoutLiterals = preg_replace("/'(?:[^']|'')*'/", "''", $clean) ?? '';
        if (preg_match('/\b(?:'.implode('|', self::FORBIDDEN).')\b|\bxp_\w+|\bsp_\w+/i', $withoutLiterals, $match)) {
            throw new ReadOnlyViolation('Sentencia rechazada por contener «'.strtoupper($match[0]).'».');
        }
    }
}

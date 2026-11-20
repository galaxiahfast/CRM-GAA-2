<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Los nombres y apellidos de todas las cuentas pasan a MAYÚSCULAS (con acentos y ñ). A partir de aquí el modelo User
     * los guarda y los muestra siempre así.
     */
    public function up(): void
    {
        DB::table('users')->select(['id', 'name', 'last_name'])->orderBy('id')->chunkById(200, function ($users): void {
            foreach ($users as $user) {
                $name = $user->name === null ? null : mb_strtoupper((string) $user->name, 'UTF-8');
                $lastName = $user->last_name === null ? null : mb_strtoupper((string) $user->last_name, 'UTF-8');

                if ($name !== $user->name || $lastName !== $user->last_name) {
                    DB::table('users')->where('id', $user->id)->update(['name' => $name, 'last_name' => $lastName]);
                }
            }
        });
    }

    /** No se puede saber cómo estaban escritos antes (mayúsculas y minúsculas), así que no hay vuelta atrás. */
    public function down(): void
    {
    }
};

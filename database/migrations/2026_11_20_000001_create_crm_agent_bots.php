<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Agentes (bots) del CRM: Lucía (soporte), Mateo (diseño) e Ian (desarrollo); los tres con «del CRM». Cada uno con su cuenta, rol de
     * Administrador, foto de perfil y descripción. La cuenta de «Sofia Soporte (bot)» se convierte en Lucía para que sus
     * conversaciones y mensajes anteriores se conserven. Las cuentas no tienen una contraseña conocida: nadie inicia sesión con ellas.
     */
    public function up(): void
    {
        // En las pruebas automáticas cada prueba crea las cuentas que necesita
        if (app()->runningUnitTests()) {
            return;
        }

        $role = Role::query()->where('permission_profile', Role::PROFILE_ADMINISTRATOR)->orderBy('id')->first()
            ?? Role::query()->create(['role' => 'Administrador', 'description' => 'Acceso completo', 'permission_profile' => Role::PROFILE_ADMINISTRATOR]);
        $disk = Storage::disk((string) config('jetstream.profile_photo_disk', 'public'));
        $previousSupportEmail = (string) config('support.automated_user.previous_email', 'sofia.soporte@sistema.local');

        foreach ((array) config('support.bots', []) as $key => $bot) {
            $user = User::query()->where('email', $bot['email'])->first();

            if ($user === null && $key === 'soporte') {
                $user = User::query()->where('email', $previousSupportEmail)->first();
            }
            if ($user === null && ! empty($bot['previous_email'])) {
                $user = User::query()->where('email', $bot['previous_email'])->first();
            }

            // Foto de perfil de 512px (ligera para las listas); la original de 1024x1024 se queda en public/img/bots
            // El nombre lleva una huella del contenido: si la foto cambia, cambia su dirección y el navegador no muestra la anterior
            $source = public_path((string) $bot['photo']);
            $base = pathinfo((string) $bot['photo'], PATHINFO_FILENAME);
            $photoPath = 'profile-photos/bots/'.$base.(is_file($source) ? '-'.substr((string) md5_file($source), 0, 8) : '').'.png';
            if (is_file($source)) {
                foreach ($disk->files('profile-photos/bots') as $old) {
                    if (str_starts_with(basename($old), $base) && $old !== $photoPath) {
                        $disk->delete($old);
                    }
                }
                $disk->put($photoPath, $this->resized($source, 512));
            }

            $attributes = [
                'name' => $bot['name'],
                'last_name' => $bot['last_name'],
                'email' => $bot['email'],
                'role_id' => $role->id,
                'profile_description' => $bot['description'],
                'profile_photo_path' => $disk->exists($photoPath) ? $photoPath : null,
            ];

            if ($user) {
                $user->forceFill($attributes)->saveQuietly();
            } else {
                User::query()->forceCreate($attributes + [
                    'email_verified_at' => now(),
                    'password' => bcrypt(Str::random(64)),
                ]);
            }
        }
    }

    private function resized(string $source, int $size): string
    {
        $image = function_exists('imagecreatefrompng') ? @imagecreatefrompng($source) : false;
        if (! $image) {
            return (string) file_get_contents($source);
        }

        // Conserva el fondo transparente
        $small = imagecreatetruecolor($size, $size);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagefill($small, 0, 0, imagecolorallocatealpha($small, 0, 0, 0, 127));
        imagecopyresampled($small, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));
        ob_start();
        imagepng($small, null, 9);

        return (string) ob_get_clean();
    }

    public function down(): void
    {
        // Lucía vuelve a ser Sofia (conserva su historial); las cuentas de Mateo e Ian se quitan.
        $support = User::query()->where('email', 'lucia.soporte@sistema.local')->first();
        $support?->forceFill([
            'name' => 'Sofia',
            'last_name' => 'Soporte (bot)',
            'email' => 'sofia.soporte@sistema.local',
            'profile_photo_path' => null,
            'profile_description' => null,
        ])->saveQuietly();

        User::query()->whereIn('email', ['mateo.diseno@sistema.local', 'ian.programacion@sistema.local', 'andres.programacion@sistema.local'])->delete();
    }
};

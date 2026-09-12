<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        $categories = [
            ['Impuestos Estatales', 'Obligaciones y contribuciones de carácter estatal.'],
            ['Impuestos Federales', 'Obligaciones fiscales administradas a nivel federal.'],
            ['Impuestos Especiales', 'Contribuciones y obligaciones fiscales especializadas.'],
            ['Contabilidad', 'Registro, conciliación y control de la información contable.'],
            ['Nómina y Seguridad Social', 'Procesamiento de nómina y cumplimiento de seguridad social.'],
            ['Declaraciones e Informativas', 'Preparación y presentación de declaraciones e informativas.'],
            ['Facturación Electrónica', 'Emisión, validación y seguimiento de comprobantes fiscales.'],
            ['Auditoría y Revisión', 'Revisión de controles, operaciones y cumplimiento documental.'],
            ['Trámites y Gestoría', 'Gestiones ante autoridades e instituciones públicas o privadas.'],
            ['Consultoría Fiscal', 'Análisis y acompañamiento especializado en materia fiscal.'],
            ['Control Administrativo', 'Actividades administrativas y de seguimiento interno.'],
            ['Atención a Clientes', 'Comunicación, seguimiento y soporte directo a clientes.'],
        ];

        foreach ($categories as [$name, $description]) {
            if (! DB::table('services')->where('service', $name)->exists()) {
                DB::table('services')->insert([
                    'service' => $name,
                    'description' => $description,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // El catálogo puede haber sido modificado por usuarios. No se elimina
        // información empresarial al revertir una migración de datos iniciales.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('control_de_horas', function (Blueprint $table) {
            // Cambiados a CamelCase para hacer espejo exacto con tu MySQL física
            $table->string('employeeID', 50); 
            $table->string('personName', 255)->nullable();
            $table->dateTime('authDateTime');
            $table->date('authDate')->nullable();
            $table->time('authTime')->nullable();
            $table->string('direction', 10)->nullable(); // IN o OUT
            $table->string('deviceName', 255)->nullable();

            // Llave primaria compuesta usando los nombres de columna corregidos
            $table->primary(['employeeID', 'authDateTime']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Corregido para que borre la tabla correcta ('control_de_horas') en caso de un rollback
        Schema::dropIfExists('control_de_horas');
    }
};
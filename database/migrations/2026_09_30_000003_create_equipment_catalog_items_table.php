<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->string('equipment_type', 80)->index();
            $table->string('brand', 100)->index();
            $table->string('model', 120)->index();
            $table->string('keywords', 255)->default('');
            $table->timestamps();

            $table->unique(['equipment_type', 'brand', 'model'], 'equipment_catalog_unique_item');
        });

        $now = now();
        $items = [
            ['Laptop', 'Lenovo', 'ThinkPad T14', 'notebook empresarial intel amd'],
            ['Laptop', 'Lenovo', 'ThinkPad T14s', 'notebook empresarial ultraligera'],
            ['Laptop', 'Lenovo', 'ThinkPad X1 Carbon', 'notebook empresarial ultraligera'],
            ['Laptop', 'Lenovo', 'ThinkPad L14', 'notebook empresarial'],
            ['Laptop', 'Lenovo', 'ThinkPad E14', 'notebook empresarial'],
            ['Laptop', 'Dell', 'Latitude 5420', 'notebook empresarial'],
            ['Laptop', 'Dell', 'Latitude 5430', 'notebook empresarial'],
            ['Laptop', 'Dell', 'Latitude 5440', 'notebook empresarial'],
            ['Laptop', 'Dell', 'Latitude 5520', 'notebook empresarial'],
            ['Laptop', 'Dell', 'XPS 13', 'notebook ultrabook'],
            ['Laptop', 'HP', 'ProBook 450 G8', 'notebook empresarial'],
            ['Laptop', 'HP', 'ProBook 450 G9', 'notebook empresarial'],
            ['Laptop', 'HP', 'EliteBook 840 G8', 'notebook empresarial'],
            ['Laptop', 'HP', 'EliteBook 840 G9', 'notebook empresarial'],
            ['Laptop', 'HP', 'ZBook Firefly', 'notebook workstation'],
            ['Laptop', 'Acer', 'TravelMate P2', 'notebook empresarial'],
            ['Laptop', 'Acer', 'Aspire 5', 'notebook'],
            ['Laptop', 'Asus', 'ExpertBook B1', 'notebook empresarial'],
            ['Laptop', 'Asus', 'VivoBook 15', 'notebook'],
            ['Laptop', 'Apple', 'MacBook Air M1', 'notebook mac'],
            ['Laptop', 'Apple', 'MacBook Air M2', 'notebook mac'],
            ['Laptop', 'Apple', 'MacBook Pro 14', 'notebook mac'],
            ['PC de escritorio', 'Dell', 'OptiPlex 3080', 'desktop empresarial'],
            ['PC de escritorio', 'Dell', 'OptiPlex 3090', 'desktop empresarial'],
            ['PC de escritorio', 'Dell', 'OptiPlex 5000', 'desktop empresarial'],
            ['PC de escritorio', 'Dell', 'OptiPlex 7010', 'desktop empresarial'],
            ['PC de escritorio', 'HP', 'ProDesk 400 G7', 'desktop empresarial'],
            ['PC de escritorio', 'HP', 'ProDesk 400 G9', 'desktop empresarial'],
            ['PC de escritorio', 'HP', 'EliteDesk 800 G6', 'desktop empresarial'],
            ['PC de escritorio', 'HP', 'EliteDesk 800 G9', 'desktop empresarial'],
            ['PC de escritorio', 'Lenovo', 'ThinkCentre M70s', 'desktop empresarial'],
            ['PC de escritorio', 'Lenovo', 'ThinkCentre M75s', 'desktop empresarial'],
            ['PC de escritorio', 'Lenovo', 'ThinkCentre M90q', 'desktop mini empresarial'],
            ['Impresora', 'HP', 'LaserJet Pro M404dn', 'impresora laser'],
            ['Impresora', 'HP', 'LaserJet Pro MFP M428fdw', 'impresora laser multifuncional'],
            ['Impresora', 'HP', 'LaserJet Pro MFP 4103fdw', 'impresora laser multifuncional'],
            ['Impresora', 'Epson', 'EcoTank L3250', 'impresora tinta multifuncional'],
            ['Impresora', 'Epson', 'EcoTank L4260', 'impresora tinta multifuncional'],
            ['Impresora', 'Epson', 'EcoTank L6270', 'impresora tinta multifuncional'],
            ['Impresora', 'Brother', 'DCP-L2540DW', 'impresora laser multifuncional'],
            ['Impresora', 'Brother', 'MFC-L8900CDW', 'impresora laser multifuncional'],
            ['Impresora', 'Canon', 'imageCLASS MF445dw', 'impresora laser multifuncional'],
            ['Monitor', 'Dell', 'P2422H', 'monitor display 24 pulgadas'],
            ['Monitor', 'Dell', 'P2722H', 'monitor display 27 pulgadas'],
            ['Monitor', 'HP', 'P24h G4', 'monitor display 24 pulgadas'],
            ['Monitor', 'HP', 'E24 G4', 'monitor display 24 pulgadas'],
            ['Monitor', 'Lenovo', 'ThinkVision T24i-20', 'monitor display 24 pulgadas'],
            ['Monitor', 'Samsung', 'F24T350', 'monitor display 24 pulgadas'],
            ['Servidor', 'Dell', 'PowerEdge T40', 'server torre'],
            ['Servidor', 'Dell', 'PowerEdge T150', 'server torre'],
            ['Servidor', 'Dell', 'PowerEdge R350', 'server rack'],
            ['Servidor', 'Dell', 'PowerEdge R550', 'server rack'],
            ['Servidor', 'HPE', 'ProLiant ML30 Gen10', 'server torre hp'],
            ['Servidor', 'HPE', 'ProLiant DL360 Gen10', 'server rack hp'],
            ['Servidor', 'HPE', 'ProLiant MicroServer Gen10 Plus', 'server torre hp'],
            ['Servidor', 'Lenovo', 'ThinkSystem ST50', 'server torre'],
            ['Tablet', 'Apple', 'iPad 10', 'tablet ios'],
            ['Tablet', 'Apple', 'iPad Air', 'tablet ios'],
            ['Tablet', 'Samsung', 'Galaxy Tab A8', 'tablet android'],
            ['Tablet', 'Samsung', 'Galaxy Tab S9', 'tablet android'],
            ['Teléfono', 'Apple', 'iPhone 13', 'smartphone ios'],
            ['Teléfono', 'Apple', 'iPhone 14', 'smartphone ios'],
            ['Teléfono', 'Samsung', 'Galaxy S23', 'smartphone android'],
            ['Teléfono', 'Motorola', 'Moto G84', 'smartphone android'],
            ['Otro', 'Ubiquiti', 'UniFi Dream Machine Pro', 'router gateway red udm pro'],
            ['Otro', 'Ubiquiti', 'UniFi U6 Pro', 'access point punto de acceso red'],
            ['Otro', 'TP-Link', 'EAP610', 'access point punto de acceso red'],
            ['Otro', 'TP-Link', 'TL-SG2428P', 'switch administrable poe red'],
            ['Otro', 'Logitech', 'Rally Bar', 'videoconferencia camara audio'],
            ['Otro', 'APC', 'Smart-UPS 1500', 'ups no break respaldo energia'],
        ];

        DB::table('equipment_catalog_items')->insert(array_map(
            fn (array $item): array => [
                'equipment_type' => $item[0],
                'brand' => $item[1],
                'model' => $item[2],
                'keywords' => $item[3],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $items,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_catalog_items');
    }
};

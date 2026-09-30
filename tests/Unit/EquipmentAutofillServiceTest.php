<?php

namespace Tests\Unit;

use App\Services\Support\EquipmentAutofillService;
use App\Services\Support\EquipmentCatalogService;
use App\Services\Support\EquipmentOcrService;
use Mockery;
use Tests\TestCase;

class EquipmentAutofillServiceTest extends TestCase
{
    public function test_it_extracts_common_equipment_fields_from_manual_text(): void
    {
        $ocr = Mockery::mock(EquipmentOcrService::class);
        $catalog = Mockery::mock(EquipmentCatalogService::class);
        $catalog->shouldReceive('find')->once()->andReturnNull();
        $service = new EquipmentAutofillService($ocr, $catalog);

        $result = $service->fromText('Lenovo ThinkPad T14 S/N PF3ABC123 cargador');

        $this->assertSame('Lenovo', $result['fields']['brand']);
        $this->assertSame('ThinkPad T14', $result['fields']['model']);
        $this->assertSame('PF3ABC123', $result['fields']['serial_number']);
        $this->assertSame('Laptop', $result['fields']['equipment_type']);
        $this->assertSame('Cargador', $result['fields']['accessories']);
        $this->assertFalse($result['catalog_used']);
    }

    public function test_image_flow_uses_ocr_text_and_keeps_locally_detected_values(): void
    {
        $ocr = Mockery::mock(EquipmentOcrService::class);
        $ocr->shouldReceive('extract')
            ->once()
            ->with('temporary-label.jpg')
            ->andReturn("HP ProBook 450 G8\nS/N 5CD1234ABC");
        $catalog = Mockery::mock(EquipmentCatalogService::class);
        $catalog->shouldReceive('find')->once()->andReturn([
            'brand' => 'Otra marca',
            'equipment_type' => 'Laptop',
        ]);
        $service = new EquipmentAutofillService($ocr, $catalog);

        $result = $service->fromImage('temporary-label.jpg');

        $this->assertSame('HP', $result['fields']['brand']);
        $this->assertSame('ProBook 450 G8', $result['fields']['model']);
        $this->assertSame('5CD1234ABC', $result['fields']['serial_number']);
        $this->assertSame('Laptop', $result['fields']['equipment_type']);
        $this->assertTrue($result['catalog_used']);
    }

    public function test_valid_barcode_can_be_enriched_by_the_optional_free_catalog(): void
    {
        $ocr = Mockery::mock(EquipmentOcrService::class);
        $catalog = Mockery::mock(EquipmentCatalogService::class);
        $catalog->shouldReceive('find')
            ->once()
            ->with('012345678905', '012345678905')
            ->andReturn([
                'brand' => 'Logitech',
                'model' => 'M185',
                'equipment_type' => 'Otro',
            ]);
        $service = new EquipmentAutofillService($ocr, $catalog);

        $result = $service->fromText('012345678905');

        $this->assertSame('Logitech', $result['fields']['brand']);
        $this->assertSame('M185', $result['fields']['model']);
        $this->assertSame('Otro', $result['fields']['equipment_type']);
        $this->assertTrue($result['catalog_used']);
    }
}

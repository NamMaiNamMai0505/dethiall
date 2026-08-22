<?php

namespace Tests\Feature;

use App\Models\DigitalSignature;
use App\Services\DigitalSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalSignaturePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_template_seed_never_overwrites_updated_signature_information(): void
    {
        config()->set('lhl_export.signers', [[
            'key' => DigitalSignature::SLOT_NGUOI_LAM_LICH,
            'name' => 'Tên từ cấu hình',
            'role_line1' => 'CHỨC DANH CŨ',
            'role_line2' => '',
            'image' => 'signatures/original.png',
            'match_names' => ['Tên từ cấu hình'],
        ]]);

        $service = app(DigitalSignatureService::class);
        $this->assertSame(1, $service->seedSystemTemplates());

        $signature = DigitalSignature::query()->firstOrFail();
        $signature->update([
            'display_name' => 'Tên đã cập nhật',
            'role_line1' => 'CHỨC DANH MỚI',
            'image_path' => 'signatures/updated.png',
            'is_active' => false,
        ]);

        $this->assertSame(0, $service->seedSystemTemplates());
        $this->assertDatabaseHas('digital_signatures', [
            'id' => $signature->id,
            'display_name' => 'Tên đã cập nhật',
            'role_line1' => 'CHỨC DANH MỚI',
            'image_path' => 'signatures/updated.png',
            'is_active' => false,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Services\DigitalSignatureService;
use Illuminate\Database\Seeder;

class DigitalSignatureSeeder extends Seeder
{
    public function run(): void
    {
        $svc = app(DigitalSignatureService::class);
        $n = $svc->seedSystemTemplates();
        $claimed = $svc->claimAllUsers();
        $this->command?->info("Digital signatures: seeded {$n} templates, claimed {$claimed} for users.");
    }
}

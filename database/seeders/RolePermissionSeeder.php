<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * NOTE: This seeder is ONLY for development/testing.
     * In PRODUCTION, use: php artisan permissions:sync
     */
    public function run(): void
    {
        $this->command->info('🔐 Setting up permissions and roles...');
        $this->command->warn('⚠️  This seeder calls the permissions:sync command.');
        $this->command->warn('⚠️  For production, run: php artisan permissions:sync directly');

        // Just call the command - single source of truth
        \Illuminate\Support\Facades\Artisan::call('permissions:sync', ['--reset' => true]);

        $this->command->info('✓ Permissions and roles setup completed!');
    }
}

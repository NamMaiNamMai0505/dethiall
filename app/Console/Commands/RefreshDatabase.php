<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RefreshDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:refresh-with-seeders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the database and run all seeders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Refreshing database...');
        
        // Fresh migrate
        Artisan::call('migrate:fresh');
        $this->info('✅ Database migrated fresh');
        
        // Run seeders
        Artisan::call('db:seed');
        $this->info('✅ All seeders have been run');
        
        $this->info('🎉 Database refreshed successfully with sample data!');
        
        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowDatabaseStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show database statistics and sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Database Statistics');
        $this->line('========================');

        $stats = [
            'Users' => User::count(),
            'Units' => DB::table('units')->count(),
            'Specializations' => DB::table('specializations')->count(),
            'Instructors' => DB::table('instructors')->count(),
            'Classes' => DB::table('classes')->count(),
            'Training Schedules' => DB::table('training_schedules')->count(),
        ];

        foreach ($stats as $table => $count) {
            $this->info(sprintf('%-20s: %d records', $table, $count));
        }

        $this->line('');
        $this->info('🔑 Admin Login:');
        $this->line('Email: admin@example.com');
        $this->line('Password: password');

        $this->line('');
        $this->info('📚 Sample Specializations:');
        $specializations = DB::table('specializations')->select('code', 'name')->get();
        foreach ($specializations as $spec) {
            $this->line("- {$spec->major_code}: {$spec->name}");
        }

        $this->line('');
        $this->info('👨‍🏫 Sample Instructors:');
        $instructors = DB::table('instructors')
            ->join('specializations', 'instructors.specialization_id', '=', 'specializations.id')
            ->select('instructors.name', 'instructors.code', 'specializations.major_code as spec_code')
            ->limit(5)
            ->get();
        foreach ($instructors as $instructor) {
            $this->line("- {$instructor->code}: {$instructor->name} ({$instructor->spec_code})");
        }

        $this->line('');
        $this->info('🎓 Active Classes:');
        $classes = DB::table('classes')
            ->join('specializations', 'classes.specialization_id', '=', 'specializations.id')
            ->select('classes.code', 'classes.name', 'specializations.major_code as spec_code', 'classes.current_students', 'classes.max_students')
            ->where('classes.is_active', true)
            ->limit(5)
            ->get();
        foreach ($classes as $class) {
            $this->line("- {$class->code}: {$class->name} ({$class->current_students}/{$class->max_students} students)");
        }

        return Command::SUCCESS;
    }
}

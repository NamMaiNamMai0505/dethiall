<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('standard_object_types')) {
            return;
        }

        if (! Schema::hasColumn('standard_object_types', 'administrative_hours')) {
            Schema::table('standard_object_types', function (Blueprint $table): void {
                $table->decimal('administrative_hours', 8, 2)
                    ->default(0)
                    ->after('research_hours');
            });
        }

        foreach ([
            '01' => 840,
            '02' => 1140,
            '03' => 1290,
        ] as $code => $hours) {
            DB::table('standard_object_types')
                ->where('code', $code)
                ->update(['administrative_hours' => $hours]);
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('standard_object_types')
            && Schema::hasColumn('standard_object_types', 'administrative_hours')
        ) {
            Schema::table('standard_object_types', function (Blueprint $table): void {
                $table->dropColumn('administrative_hours');
            });
        }
    }
};

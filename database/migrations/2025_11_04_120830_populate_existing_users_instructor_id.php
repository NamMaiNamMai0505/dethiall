<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data migration: link users (type instructor) to instructors by email.
 * Dùng DB::table — không Eloquent SoftDeletes (cột deleted_at có thể chưa tồn tại lúc migrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('instructors')) {
            return;
        }

        $instructorUsers = DB::table('users')
            ->where('user_type', 'instructor')
            ->whereNull('instructor_id')
            ->get();

        foreach ($instructorUsers as $user) {
            $instructorQuery = DB::table('instructors')->where('email', $user->email);
            if (Schema::hasColumn('instructors', 'deleted_at')) {
                $instructorQuery->whereNull('deleted_at');
            }
            $instructor = $instructorQuery->first();

            if (! $instructor) {
                continue;
            }

            $existingUser = DB::table('users')
                ->where('instructor_id', $instructor->id)
                ->exists();

            if (! $existingUser) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['instructor_id' => $instructor->id]);
            }
        }
    }

    public function down(): void
    {
        // Data migration — không rollback tự động
    }
};

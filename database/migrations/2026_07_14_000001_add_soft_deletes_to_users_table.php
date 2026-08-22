<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-delete users/students so Trash can record and restore them.
     * Email unique is enforced for active rows only (validation), not DB-wide.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Drop global unique so soft-deleted emails can be reused
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['email']);
            });
        } catch (\Throwable) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique('users_email_unique');
                });
            } catch (\Throwable) {
                // Index already absent
            }
        }

        // Keep a non-unique index for email lookups
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->index('email');
            });
        } catch (\Throwable) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        try {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['email']);
            });
        } catch (\Throwable) {
            // ignore
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        try {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        } catch (\Throwable) {
            // ignore
        }
    }
};

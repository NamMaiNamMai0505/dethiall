<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_research_announcements', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->string('status', 30)->default('OPEN');
            $table->string('template_path')->nullable();
            $table->string('template_name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('research_category_id')->constrained('research_categories')->restrictOnDelete();
            $table->foreignId('announcement_id')->nullable()->constrained('scientific_research_announcements')->nullOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('topic')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->unsignedInteger('participant_count')->default(1);
            $table->string('status', 30)->default('DRAFT');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->constrained('scientific_research_registrations')->cascadeOnDelete();
            $table->date('completed_on')->nullable();
            $table->unsignedSmallInteger('implementation_year')->nullable();
            $table->text('summary')->nullable();
            $table->string('status', 30)->default('SUBMITTED');
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_files', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type', 40);
            $table->unsignedBigInteger('owner_id');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['owner_type', 'owner_id'], 'scientific_research_files_owner_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_research_files');
        Schema::dropIfExists('scientific_research_results');
        Schema::dropIfExists('scientific_research_registrations');
        Schema::dropIfExists('scientific_research_announcements');
    }
};

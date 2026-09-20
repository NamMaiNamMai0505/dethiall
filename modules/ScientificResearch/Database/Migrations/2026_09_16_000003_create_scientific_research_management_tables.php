<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_research_staff_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('unit_name')->nullable();
            $table->string('academic_title')->nullable();
            $table->string('degree')->nullable();
            $table->string('specialization')->nullable();
            $table->string('research_fields')->nullable();
            $table->text('scientific_achievements')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('school_year')->nullable();
            $table->unsignedSmallInteger('plan_year')->nullable();
            $table->string('unit_name')->nullable();
            $table->text('objectives')->nullable();
            $table->text('assigned_tasks')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_councils', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained('scientific_research_registrations')->nullOnDelete();
            $table->string('name');
            $table->string('type', 50)->default('APPRAISAL');
            $table->dateTime('meeting_at')->nullable();
            $table->string('location')->nullable();
            $table->text('decision_number')->nullable();
            $table->text('conclusion')->nullable();
            $table->string('status', 30)->default('PLANNED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_council_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('council_id')->constrained('scientific_research_councils')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('role')->nullable();
            $table->text('comment')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('scientific_research_fundings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained('scientific_research_registrations')->nullOnDelete();
            $table->string('item_name');
            $table->string('type', 30)->default('ESTIMATE');
            $table->decimal('amount', 15, 2)->default(0);
            $table->date('spent_on')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained('scientific_research_registrations')->nullOnDelete();
            $table->string('type', 50);
            $table->string('title');
            $table->string('authors')->nullable();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('RECORDED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('scientific_research_repository_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->nullable()->constrained('scientific_research_registrations')->nullOnDelete();
            $table->string('title');
            $table->string('document_type', 50)->default('OTHER');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->text('keywords')->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_research_repository_documents');
        Schema::dropIfExists('scientific_research_products');
        Schema::dropIfExists('scientific_research_fundings');
        Schema::dropIfExists('scientific_research_council_members');
        Schema::dropIfExists('scientific_research_councils');
        Schema::dropIfExists('scientific_research_plans');
        Schema::dropIfExists('scientific_research_staff_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ScientificResearch\Models\ScientificResearchRegistration;
use Modules\ScientificResearch\Models\ScientificResearchRegistrationExtension;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scientific_research_registration_extensions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registration_id')->constrained('scientific_research_registrations')->cascadeOnDelete();
            $table->date('requested_until')->nullable();
            $table->text('request_note')->nullable();
            $table->string('previous_status', 30)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->string('status', 30)->default(ScientificResearchRegistrationExtension::STATUS_PENDING);
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('scientific_research_registrations')
            ->where(function ($query): void {
                $query->whereNotNull('extension_requested_at')
                    ->orWhereNotNull('extension_requested_until')
                    ->orWhereNotNull('extension_request_note');
            })
            ->orderBy('id')
            ->get()
            ->each(function ($registration): void {
                $status = ScientificResearchRegistrationExtension::STATUS_PENDING;

                if ($registration->extension_reviewed_at) {
                    $approvedUntil = $registration->extended_until ?: $registration->end_date;
                    $status = $approvedUntil && $registration->extension_requested_until && $approvedUntil === $registration->extension_requested_until
                        ? ScientificResearchRegistrationExtension::STATUS_APPROVED
                        : ScientificResearchRegistrationExtension::STATUS_REJECTED;
                } elseif ($registration->status !== ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED) {
                    $status = ScientificResearchRegistrationExtension::STATUS_APPROVED;
                }

                DB::table('scientific_research_registration_extensions')->insert([
                    'registration_id' => $registration->id,
                    'requested_until' => $registration->extension_requested_until,
                    'request_note' => $registration->extension_request_note,
                    'previous_status' => $registration->extension_previous_status,
                    'requested_by' => $registration->user_id,
                    'requested_at' => $registration->extension_requested_at ?: $registration->updated_at,
                    'status' => $status,
                    'review_note' => $registration->extension_review_note,
                    'reviewed_by' => $registration->extension_reviewed_by,
                    'reviewed_at' => $registration->extension_reviewed_at,
                    'created_at' => $registration->extension_requested_at ?: $registration->created_at,
                    'updated_at' => $registration->extension_reviewed_at ?: $registration->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('scientific_research_registration_extensions');
    }
};

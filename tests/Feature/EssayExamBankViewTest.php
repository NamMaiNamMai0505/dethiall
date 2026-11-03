<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Modules\EssayExam\Models\EssayExam;
use Modules\EssayExam\Models\EssayExamQuestion;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Tests\TestCase;

class EssayExamBankViewTest extends TestCase
{
    public function test_bank_renders_exams_with_missing_subject_or_specialization(): void
    {
        $subject = new Subject(['name' => 'Môn chưa phân ngành']);
        $subject->setRelation('specialization', null);
        $assignedSubject = new Subject(['name' => 'Môn đã phân ngành', 'specialization_id' => 1]);
        $assignedSubject->setRelation('specialization', new Specialization(['code' => 'DD', 'name' => 'Điều dưỡng']));

        $exams = collect([null, $subject, $assignedSubject])->map(function ($subject, $index) {
            $exam = new EssayExam(['code' => 'TL000'.($index + 1)]);
            $exam->id = $index + 1;
            $exam->setRelation('subject', $subject);
            $exam->setRelation('questions', collect([new EssayExamQuestion([
                'paper_status' => 'APPROVED', 'paper_number' => 1,
            ])]));

            return $exam;
        });

        // Render the bank content independently of the admin layout and its database queries.
        $template = str_replace([
            "@extends('layouts.admin')",
            "@include('partials.module-menu', ['module' => 'exam'])",
        ], '', file_get_contents(base_path('modules/EssayExam/Views/bank.blade.php')));
        $html = Blade::render($template."\n@yield('content')", [
            'exams' => new LengthAwarePaginator($exams, 3, 20),
        ]);

        $this->assertStringContainsString('Chưa phân ngành', $html);
        $this->assertStringContainsString('Điều dưỡng', $html);
        $this->assertStringContainsString('Môn chưa phân ngành', $html);
        foreach (['TL0001D01', 'TL0002D01', 'TL0003D01'] as $code) {
            $this->assertStringContainsString($code, $html);
        }
    }
}

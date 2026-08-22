<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Subject\Models\Subject;
use Modules\Specialization\Models\Specialization;
use App\Models\User;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user as creator
        $user = User::first();
        $userId = $user ? $user->id : 1;

        // Get specializations
        $specializations = Specialization::all();

        if ($specializations->isEmpty()) {
            $this->command->warn('No specializations found. Please run SpecializationSeeder first.');
            return;
        }

        $subjects = [
            [
                'name' => 'Điều dưỡng cơ bản',
                'code' => 'DDCB',
                'description' => 'Môn học điều dưỡng cơ bản',
                'specialization_name' => 'Điều dưỡng',
                'credits' => 3,
                'theory_hours' => 30,
                'practice_hours' => 30,
                'self_study_hours' => 30,
                'level' => 'basic',
                'prerequisites' => ['Giải phẫu sinh lý'],
                'assessment_method' => 'multiple_choice_questions',
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Trung cấp y sĩ đa khoa',
                'code' => 'DDTC',
                'description' => 'Môn học Trung cấp y sĩ đa khoa',
                'specialization_name' => 'Cao đẳng Y sĩ đa khoa', 
                'credits' => 3,
                'theory_hours' => 30,
                'practice_hours' => 30,
                'self_study_hours' => 30,
                'level' => 'basic',
                'prerequisites' => ['Toán rời rạc'],
                'assessment_method' => 'multiple_choice_questions',
                'is_required' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Kỹ năng cơ bản nghề bếp',
                'code' => 'KNCBNB',
                'description' => 'Môn học Kỹ năng cơ bản nghề bếp',
                'specialization_name' => 'Kĩ thuật chế biến món ăn', 
                'credits' => 3,
                'theory_hours' => 30,
                'practice_hours' => 30,
                'self_study_hours' => 30,
                'level' => 'basic',
                'prerequisites' => [''],
                'assessment_method' => 'project',
                'is_required' => true,
                'is_active' => true,
            ],
            
        ];

        foreach ($subjects as $subjectData) {
            // Find specialization by name
            $specialization = $specializations->where('name', $subjectData['specialization_name'])->first();

            if (!$specialization) {
                $this->command->warn("Specialization '{$subjectData['specialization_name']}' not found for subject '{$subjectData['name']}'");
                continue;
            }

            Subject::create([
                'name' => $subjectData['name'],
                'code' => $subjectData['code'],
                'description' => $subjectData['description'],
                'specialization_id' => $specialization->id,
                'credits' => $subjectData['credits'],
                'theory_hours' => $subjectData['theory_hours'],
                'practice_hours' => $subjectData['practice_hours'],
                'self_study_hours' => $subjectData['self_study_hours'],
                'level' => $subjectData['level'],
                'prerequisites' => $subjectData['prerequisites'],
                'assessment_method' => $subjectData['assessment_method'],
                'is_required' => $subjectData['is_required'],
                'is_active' => $subjectData['is_active'],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }

        $this->command->info('Created ' . count($subjects) . ' subjects successfully.');
    }
}

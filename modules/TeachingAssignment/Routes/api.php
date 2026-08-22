use Illuminate\Support\Facades\Route;
use Modules\Instructor\Models\Instructor;
use Modules\Subject\Models\Subject;

Route::get('/instructors', function () {
    $unitId = request('unit_id');
    return Instructor::where('unit_id', $unitId)
        ->select('id','name')
        ->orderBy('name')
        ->get();
});

Route::get('/subjects', function () {
    $specializationId = request('specialization_id');
    return Subject::where('specialization_id', $specializationId)
        ->select('id','name')
        ->orderBy('name')
        ->get();
});

<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourseController extends Controller
{

    public function index()
    {
        return Course::with([
            'subject',
            'studentClass',
            'studyPeriod.semester.academicYear',
            'lecturer.faculty'
        ])->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:student_classes,id',
            'study_period_id' => 'required|exists:study_periods,id',
            'lecturer_id' => 'required|exists:lecturers,id',
        ]);

        $course = Course::create($validatedData);

        return response()->json($course, Response::HTTP_CREATED);
    }

    public function show(Course $course)
    {
        return $course->load([
            'subject.department.faculty.facility',
            'studentClass',
            'studyPeriod.semester.academicYear',
            'lecturer.faculty.facility'
        ]);
    }

    public function update(Request $request, Course $course)
    {
        $validatedData = $request->validate([
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'class_id' => 'sometimes|required|exists:student_classes,id',
            'study_period_id' => 'sometimes|required|exists:study_periods,id',
            'lecturer_id' => 'sometimes|required|exists:lecturers,id',
        ]);

        $course->update($validatedData);

        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudyPeriod; 
use App\Models\Course; 
use Carbon\Carbon;

class StudentCourseController extends Controller
{
    /**
     * Lấy danh sách các lớp học phần của sinh viên,
     * nhóm theo đợt học (StudyPeriod) và sắp xếp giảm dần.
     */
    public function getMyCoursesByPeriod(Request $request)
    {
        // Lấy sinh viên đã đăng nhập
        $student = $request->user();
        $classId = $student->class_id;

        // 1. Lấy các đợt học (StudyPeriod) mà lớp của sinh viên này có học
        $periods = StudyPeriod::whereHas('courses', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        })
        ->with([
            // 2. Chỉ tải các lớp học phần (courses) của lớp này
            'courses' => function ($q) use ($classId) {
                $q->where('class_id', $classId)
                  // Tải kèm thông tin môn học và giảng viên
                  ->with(['subject:id,name', 'lecturer:id,full_name']);
            }
        ])
        ->orderBy('start_date', 'desc') // Sắp xếp giảm dần theo ngày bắt đầu
        ->get();

        // 3. Định dạng lại dữ liệu trả về cho Flutter
        $formattedData = $periods->map(function ($period) {
            return [
                'id' => $period->id,
                'start_date' => Carbon::parse($period->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($period->end_date)->format('d/m/Y'),
                
                // Lấy danh sách các lớp học phần đã lọc
                'courses' => $period->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'subject_name' => $course->subject->name,
                        // Sinh viên sẽ muốn biết ai dạy
                        'lecturer_name' => $course->lecturer->full_name, 
                    ];
                }),
            ];
        });

        return response()->json(['status' => true, 'data' => $formattedData], 200);
    }
}
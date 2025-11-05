<?php

namespace App\Http\Controllers\Api\lecturers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudyPeriod;
use App\Models\Course;
use App\Models\AttendanceSession;
use Carbon\Carbon;

class LecturerCourseController extends Controller
{
    /**
     * Lấy danh sách các lớp học phần của giảng viên,
     * nhóm theo đợt học (StudyPeriod) và sắp xếp giảm dần.
     */
    public function getCoursesByPeriod(Request $request)
    {
        $lecturer = $request->user();

        // 1. Lấy các đợt học (StudyPeriod) mà giảng viên này có dạy
        $periods = StudyPeriod::whereHas('courses', function ($q) use ($lecturer) {
            $q->where('lecturer_id', $lecturer->id);
        })
            ->with([
                // 2. Chỉ tải các lớp học phần (courses) của giảng viên này
                'courses' => function ($q) use ($lecturer) {
                    $q->where('lecturer_id', $lecturer->id)
                        // Tải kèm thông tin môn học và lớp sinh viên
                        ->with(['subject:id,name', 'studentClass:id,name']);
                }
            ])
            ->orderBy('start_date', 'desc') // Sắp xếp giảm dần theo ngày bắt đầu
            ->get();

        // 3. Định dạng lại dữ liệu trả về cho Flutter
        $formattedData = $periods->map(function ($period) {
            return [
                'id' => $period->id,
                // Định dạng ngày cho đẹp 
                'start_date' => Carbon::parse($period->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($period->end_date)->format('d/m/Y'),

                // Lấy danh sách các lớp học phần đã lọc
                'courses' => $period->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'subject_name' => $course->subject->name,
                        'class_name' => $course->studentClass->name,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedData,
        ], 200);
    }

    /**
     * Lấy tất cả các buổi học (sessions) của một lớp học phần (course).
     */
    public function getCourseSessions(Request $request, Course $course)
    {
        // 1. Kiểm tra bảo mật: Đảm bảo giảng viên này sở hữu course này
        if ($course->lecturer_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn không có quyền truy cập lớp học này.'
            ], 403);
        }

        // 2. Tải các thông tin tĩnh của Course
        $course->load(['subject:id,name', 'studentClass:id,name', 'studentClass.students']);
        $totalStudents = $course->studentClass->students->count();

        // 3. Tải tất cả các buổi học (sessions) liên quan
        $sessions = AttendanceSession::whereHas('schedule', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })
            ->with([
                'schedule:id,room_id,start_time,end_time',
                'schedule.room:id,name',
                // Đếm số sinh viên 'present' cho mỗi session
                'attendanceRecords' => function ($q) {
                    $q->where('status', 'present');
                }
            ])
            ->get(); // Lấy tất cả, Flutter sẽ tự lọc Sắp tới/Đã qua

        // 4. Định dạng lại danh sách buổi học
        $formattedSessions = $sessions->map(function ($session) use ($totalStudents) {
            return [
                'session_id' => $session->id,
                'session_date' => Carbon::parse($session->session_date, 'Asia/Ho_Chi_Minh')
                    ->toDateString(),
                'start_time' => Carbon::parse($session->schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($session->schedule->end_time)->format('H:i'),
                'room_name' => $session->schedule->room->name,
                'present_count' => $session->attendanceRecords->count(), // Đếm số sv 'present'
                'total_students' => $totalStudents,
                'status' => $session->status, // 'pending', 'active', 'closed'
            ];
        });

        // 5. Trả về JSON
        return response()->json([
            'status' => true,
            'data' => [
                'course_name' => $course->subject->name,
                'class_name' => $course->studentClass->name,
                'sessions' => $formattedSessions,
            ]
        ], 200);
    }
}

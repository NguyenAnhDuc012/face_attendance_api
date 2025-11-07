<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudyPeriod; 
use App\Models\Course; 
use App\Models\AttendanceSession;
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

    /**
     * Lấy tất cả các buổi học (sessions) của một lớp học phần (course).
     */
    public function getCourseSessions(Request $request, Course $course)
    {
        // 1. Bảo mật: Đảm bảo sinh viên này thuộc lớp của course này
        $student = $request->user();
        if ($course->class_id !== $student->class_id) {
            return response()->json([
                'status' => false,
                'message' => 'Bạn không có quyền truy cập lớp học này.'
            ], 403);
        }

        // 2. Tải các thông tin tĩnh của Course
        $course->load(['subject:id,name', 'lecturer:id,full_name']);

        // 3. Tải tất cả các buổi học (sessions) liên quan
        $sessions = AttendanceSession::whereHas('schedule', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })
        ->with([
            'schedule:id,room_id,start_time,end_time',
            'schedule.room:id,name',
            // Tải *chỉ* bản ghi điểm danh CỦA SINH VIÊN NÀY
            'attendanceRecords' => function($q) use ($student) {
                $q->where('student_id', $student->id);
            }
        ])
        ->get(); // Lấy tất cả, Flutter sẽ tự lọc

        // 4. Định dạng lại danh sách buổi học
        $formattedSessions = $sessions->map(function ($session) {
            
            // Lấy trạng thái của sinh viên (present, absent, late, ...)
            $myRecord = $session->attendanceRecords->first();
            $myStatus = $myRecord ? $myRecord->status : 'chưa có'; // Trạng thái của SV
            $myCheckInTime = $myRecord ? ($myRecord->check_in_time ? Carbon::parse($myRecord->check_in_time)->format('H:i') : null) : null;
            
            return [
                'session_id' => $session->id,
                'session_date' => Carbon::parse($session->session_date)->toDateString(), // YYYY-MM-DD
                'start_time' => Carbon::parse($session->schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($session->schedule->end_time)->format('H:i'),
                'room_name' => $session->schedule->room->name,
                'session_status' => $session->status, // 'pending', 'active', 'closed'
                'my_attendance_status' => $myStatus,
                'my_check_in_time' => $myCheckInTime,
                'attendance_mode' => $session->attendance_mode, // 'manual' hoặc 'face_recognition_qr'
            ];
        });

        // 5. Trả về JSON
        return response()->json([
            'status' => true,
            'data' => [
                'course_name' => $course->subject->name,
                'lecturer_name' => $course->lecturer->full_name,
                'sessions' => $formattedSessions,
            ]
        ], 200);
    }
}
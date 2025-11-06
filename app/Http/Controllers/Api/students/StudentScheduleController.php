<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StudentScheduleController extends Controller
{
    public function getTodaySchedule(Request $request)
    {
        $student = $request->user();
        $today = Carbon::today('Asia/Ho_Chi_Minh');

        // 1. Lấy các buổi học (session) hôm nay của lớp sinh viên
        $sessions = AttendanceSession::whereHas('schedule.course', function ($query) use ($student) {
            $query->where('class_id', $student->class_id);
        })
        ->whereDate('session_date', $today)
        ->with([
            'schedule.course.subject:id,name',
            'schedule.course.lecturer:id,full_name', // Lấy tên giảng viên
            'schedule.room:id,name',
            // Tải *chỉ* bản ghi điểm danh CỦA SINH VIÊN NÀY
            'attendanceRecords' => function($query) use ($student) {
                $query->where('student_id', $student->id);
            }
        ])
        // Sắp xếp theo giờ bắt đầu
        ->join('schedules', 'attendance_sessions.schedule_id', '=', 'schedules.id')
        ->select('attendance_sessions.*')
        ->orderBy('schedules.start_time', 'asc')
        ->get();

        // 2. Định dạng lại dữ liệu
        $formattedSchedules = $sessions->map(function ($session) {
            
            // Lấy trạng thái của sinh viên (present, absent, late, ...)
            $myRecord = $session->attendanceRecords->first();
            $myStatus = $myRecord ? $myRecord->status : 'chưa có'; // Trạng thái của SV

            return [
                'session_id' => $session->id,
                'course_id' => $session->schedule->course_id,
                'subject_name' => $session->schedule->course->subject->name,
                'lecturer_name' => $session->schedule->course->lecturer->full_name, // Tên GV
                'room_name' => $session->schedule->room->name,
                'start_time' => Carbon::parse($session->schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($session->schedule->end_time)->format('H:i'),
                'session_status' => $session->status, // 'pending', 'active', 'closed'
                'my_attendance_status' => $myStatus, // 'present', 'absent', ...
            ];
        });

        return response()->json(['status' => true, 'data' => $formattedSchedules], 200);
    }
}
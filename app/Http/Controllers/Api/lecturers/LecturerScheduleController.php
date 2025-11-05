<?php

namespace App\Http\Controllers\Api\lecturers; // Đảm bảo namespace là đúng

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Schedule;
use App\Models\AttendanceSession; // Cần import model này
use App\Models\StudentClass; // Cần import model này
use Carbon\Carbon;

class LecturerScheduleController extends Controller
{
    // lịch dạy của giảng viên hôm nay
    public function getTodaySchedule(Request $request)
    {
        // Lấy giảng viên đã xác thực
        $lecturer = $request->user();

        // Lấy ngày hôm nay
        $today = Carbon::today();

        // ===== THAY ĐỔI LOGIC TRUY VẤN =====
        
        $schedules = Schedule::whereHas('course', function ($query) use ($lecturer) {
            $query->where('lecturer_id', $lecturer->id);
        })
        // 1. THÊM MỚI: Chỉ lấy các schedule CÓ session được tạo cho ngày hôm nay
        ->whereHas('attendanceSessions', function ($query) use ($today) {
            $query->whereDate('session_date', $today);
        })
        // 2. BỎ: Không cần kiểm tra 'day_of_week' nữa, vì 'session_date' là đủ
        //    ->where('day_of_week', $todayDayOfWeek) 
        ->with([
            // 3. TỐI ƯU: Tải trước (eager-load)
            // Lấy session của ngày hôm nay VÀ đếm số record 'present'
            'attendanceSessions' => function ($query) use ($today) {
                $query->whereDate('session_date', $today)
                      ->withCount(['attendanceRecords' => function ($q) {
                          $q->where('status', 'present');
                      }]);
            },
            'course.subject:id,name', // Tên môn học
            'room:id,name', // Tên phòng
            // Tải lớp VÀ đếm tổng số sinh viên của lớp đó
            'course.studentClass' => function ($query) {
                $query->select('id', 'name') // Chỉ lấy id và name
                      ->withCount('students'); // Đếm tổng số SV
            },
        ])
        ->orderBy('start_time')
        ->get();
        // ===================================

        // Định dạng lại dữ liệu trả về cho gọn
        $formattedSchedules = $schedules->map(function ($schedule) {
            
            // Lấy session đã được tải ở trên (chỉ có 1 session cho hôm nay)
            $session = $schedule->attendanceSessions->first();

            // Nếu không có session (dù whereHas đã lọc) thì bỏ qua
            if (!$session) {
                return null;
            }

            // Dùng các_count đã được tải trước để tránh N+1 query
            $totalStudents = $schedule->course->studentClass->students_count;
            $presentStudents = $session->attendance_records_count;

            return [
                'schedule_id' => $schedule->id,
                'subject_name' => $schedule->course->subject->name,
                'room_name' => $schedule->room->name,
                'class_name' => $schedule->course->studentClass->name,
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'present_count' => $presentStudents,
                'total_students' => $totalStudents,
                'status' => $session->status, // Trạng thái từ session
            ];
        })->filter(); // ->filter() để loại bỏ các giá trị null (nếu có)

        return response()->json([
            'status' => true,
             'today' => $today->toDateString(), // 👈 hiển thị ngày hôm nay
            'data' => $formattedSchedules,
        ], 200);
    }
}
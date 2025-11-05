<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('attendance_sessions')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // ĐIỀU CHỈNH: Cho phép null
            // Vì bản ghi có thể được tạo trước khi sinh viên check-in (với trạng thái 'absent')
            $table->dateTime('check_in_time')->nullable();

            // ĐIỀU CHỈNH: Dùng enum và có giá trị mặc định là 'vắng'
            $table->enum('status', ['present', 'absent', 'late', 'excused'])
                ->default('absent');

            // Ghi nhận nguồn cập nhật
            // 'student': SV tự điểm danh (trường hợp 1)
            // 'system': Hệ thống nhận diện khuôn mặt (trường hợp 2)
            // 'lecturer': Giảng viên tự điều chỉnh
            $table->enum('source', ['student', 'system', 'lecturer'])
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};

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
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->date('session_date'); // Ngày của buổi học

            // Phương thức điểm danh cho phiên này
            $table->enum('attendance_mode', ['manual', 'face_recognition_qr'])
                ->default('manual');

            // Trạng thái của phiên (do giảng viên kiểm soát)
            // 'pending': Chờ giảng viên bắt đầu
            // 'active': Giảng viên đã bấm "Bắt đầu", sinh viên có thể điểm danh
            // 'closed': Giảng viên đã "Kết thúc"
            $table->enum('status', ['pending', 'active', 'closed'])
                ->default('pending');

            // Thời điểm bắt đầu và kết thúc thực tế (do giảng viên bấm nút)
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();

            // Token bảo mật cho QR code
            // Sẽ được tạo khi giảng viên chọn chế độ 'face_recognition_qr' và bấm "Bắt đầu"
            $table->string('qr_token')->nullable()->unique();
            $table->dateTime('qr_token_expires_at')->nullable(); // Có thể set tgian hết hạn cho QR

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};

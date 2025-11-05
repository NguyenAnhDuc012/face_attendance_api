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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->year('start_year');
            $table->year('end_year');
            $table->date('start_date')->nullable();  // Ngày bắt đầu năm học
            $table->date('end_date')->nullable();    // Ngày kết thúc năm học
            $table->boolean('is_active')->default(false); // Đánh dấu năm học hiện tại
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};

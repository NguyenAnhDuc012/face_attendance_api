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
        Schema::create('study_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')
                ->constrained('semesters')
                ->onDelete('cascade');
            $table->string('name'); // Ví dụ: "Đợt 1", "Đợt 2", hoặc "Tuần 1–5"
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_periods');
    }
};

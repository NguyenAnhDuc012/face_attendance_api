<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\StudyPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StudyPeriodController extends Controller
{

    public function index()
    {
        // Tải lồng quan hệ và sắp xếp theo trạng thái
        return StudyPeriod::with('semester.academicYear')
            ->orderBy('is_active', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(5);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'semester_id' => 'required|exists:semesters,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $studyPeriod = null;

        // Logic: Đảm bảo chỉ có 1 đợt học active TRONG CÙNG HỌC KỲ
        DB::transaction(function () use ($validatedData, &$studyPeriod) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt các đợt khác trong HỌC KỲ này về false
                StudyPeriod::where('semester_id', $validatedData['semester_id'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $studyPeriod = StudyPeriod::create($validatedData);
        });

        return response()->json($studyPeriod, Response::HTTP_CREATED);
    }


    public function show(StudyPeriod $studyPeriod)
    {
        return $studyPeriod->load('semester.academicYear');
    }

    public function update(Request $request, StudyPeriod $studyPeriod)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'semester_id' => 'sometimes|required|exists:semesters,id',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_active' => 'sometimes|nullable|boolean',
        ]);

        // Xác định học kỳ (có thể đang được cập nhật hoặc giữ nguyên)
        $semesterId = $validatedData['semester_id'] ?? $studyPeriod->semester_id;

        DB::transaction(function () use ($validatedData, $studyPeriod, $semesterId) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt các đợt khác trong HỌC KỲ này (trừ chính nó) về false
                StudyPeriod::where('semester_id', $semesterId)
                    ->where('is_active', true)
                    ->where('id', '!=', $studyPeriod->id) // Trừ đợt đang sửa
                    ->update(['is_active' => false]);
            }

            $studyPeriod->update($validatedData);
        });

        return response()->json($studyPeriod);
    }


    public function destroy(StudyPeriod $studyPeriod)
    {
        $studyPeriod->delete();
        return response()->noContent();
    }
}

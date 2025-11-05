<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{

    public function index()
    {
        // Tải kèm năm học, sắp xếp theo trạng thái active
        return Semester::with('academicYear')
            ->orderBy('is_active', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $semester = null;

        // Logic để đảm bảo chỉ có 1 học kỳ active TRONG 1 NĂM HỌC
        DB::transaction(function () use ($validatedData, &$semester) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt tất cả các học kỳ khác trong NĂM HỌC này về false
                Semester::where('academic_year_id', $validatedData['academic_year_id'])
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $semester = Semester::create($validatedData);
        });

        return response()->json($semester, Response::HTTP_CREATED);
    }

    public function show(Semester $semester)
    {
        return $semester->load('academicYear');
    }

    public function update(Request $request, Semester $semester)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'academic_year_id' => 'sometimes|required|exists:academic_years,id',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date|after_or_equal:start_date',
            'is_active' => 'sometimes|nullable|boolean',
        ]);

        // Xác định năm học (có thể đang được cập nhật hoặc giữ nguyên)
        $academicYearId = $validatedData['academic_year_id'] ?? $semester->academic_year_id;

        DB::transaction(function () use ($validatedData, $semester, $academicYearId) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt tất cả các học kỳ khác trong NĂM HỌC này (trừ chính nó) về false
                Semester::where('academic_year_id', $academicYearId)
                    ->where('is_active', true)
                    ->where('id', '!=', $semester->id) // Trừ học kỳ đang sửa
                    ->update(['is_active' => false]);
            }

            $semester->update($validatedData);
        });

        return response()->json($semester);
    }

    public function destroy(Semester $semester)
    {
        $semester->delete();
        return response()->noContent();
    }
}

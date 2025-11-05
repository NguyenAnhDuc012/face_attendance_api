<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index()
    {
        return AcademicYear::orderBy('is_active', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'start_year' => 'required|integer|digits:4|min:1900',
            'end_year' => 'required|integer|digits:4|gte:start_year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        $academicYear = null;

        // Logic để đảm bảo chỉ có 1 năm học active
        DB::transaction(function () use ($validatedData, &$academicYear) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt tất cả các năm học khác về false
                AcademicYear::where('is_active', true)->update(['is_active' => false]);
            }

            $academicYear = AcademicYear::create($validatedData);
        });

        return response()->json($academicYear, Response::HTTP_CREATED);
    }

    public function show(AcademicYear $academicYear)
    {
        return $academicYear;
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $validatedData = $request->validate([
            'start_year' => 'sometimes|required|integer|digits:4|min:1900',
            'end_year' => 'sometimes|required|integer|digits:4|gte:start_year',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        // Logic để đảm bảo chỉ có 1 năm học active
        DB::transaction(function () use ($validatedData, $academicYear) {
            if (isset($validatedData['is_active']) && $validatedData['is_active']) {
                // Đặt tất cả các năm học khác (trừ năm hiện tại) về false
                AcademicYear::where('is_active', true)
                    ->where('id', '!=', $academicYear->id)
                    ->update(['is_active' => false]);
            }

            $academicYear->update($validatedData);
        });


        return response()->json($academicYear);
    }

    public function destroy(AcademicYear $academicYear)
    {
        $academicYear->delete();

        return response()->noContent();
    }
}

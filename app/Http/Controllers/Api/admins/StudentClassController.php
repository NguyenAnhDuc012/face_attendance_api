<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\StudentClass;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StudentClassController extends Controller
{
    public function index()
    {
        return StudentClass::orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $studentClass = StudentClass::create($validatedData);

        return response()->json($studentClass, Response::HTTP_CREATED);
    }

    public function show(StudentClass $studentClass)
    {
        return $studentClass;
    }

    public function update(Request $request, StudentClass $studentClass)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
        ]);

        $studentClass->update($validatedData);

        return response()->json($studentClass);
    }

    public function destroy(StudentClass $studentClass)
    {
        $studentClass->delete();
        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        return Student::with('studentClass')->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'class_id' => 'required|exists:student_classes,id',
            'email' => 'required|email|unique:students,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);

        $student = Student::create($validatedData);

        return response()->json($student, Response::HTTP_CREATED);
    }

    public function show(Student $student)
    {
        return $student->load('studentClass');
    }

    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'dob' => 'sometimes|required|date',
            'class_id' => 'sometimes|required|exists:student_classes,id',
            'email' => 'sometimes|required|email|unique:students,email,' . $student->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $student->update($validatedData);

        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->noContent();
    }
}

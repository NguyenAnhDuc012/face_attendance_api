<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FacultyController extends Controller
{
    public function index()
    {
        return Faculty::with('facility')->orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'facility_id' => 'required|exists:facilities,id',
        ]);

        $faculty = Faculty::create($validatedData);

        return response()->json($faculty, Response::HTTP_CREATED);
    }

    public function show(Faculty $faculty)
    {
        return $faculty->load('facility');
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'facility_id' => 'sometimes|required|exists:facilities,id',
        ]);

        $faculty->update($validatedData);

        return response()->json($faculty);
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();
        return response()->noContent();
    }
}

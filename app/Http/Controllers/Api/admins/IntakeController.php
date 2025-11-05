<?php

namespace App\Http\Controllers\Api\admins;

use App\Http\Controllers\Controller;
use App\Models\Intake;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IntakeController extends Controller
{

    public function index()
    {
        return Intake::orderBy('id', 'desc')->paginate(5);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|digits:4|min:1900',
            'expected_graduation_year' => 'required|integer|digits:4|gte:start_year',
        ]);

        $intake = Intake::create($validatedData);

        return response()->json($intake, Response::HTTP_CREATED);
    }

    public function show(Intake $intake)
    {
        return $intake;
    }

    public function update(Request $request, Intake $intake)
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'start_year' => 'sometimes|required|integer|digits:4|min:1900',
            'expected_graduation_year' => 'sometimes|required|integer|digits:4|gte:start_year',
        ]);

        $intake->update($validatedData);

        return response()->json($intake);
    }

    public function destroy(Intake $intake)
    {
        $intake->delete();

        return response()->noContent();
    }
}

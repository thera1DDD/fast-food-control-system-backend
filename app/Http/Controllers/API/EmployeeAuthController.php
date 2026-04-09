<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $employee = Employee::query()
            ->where('name', $validated['name'])
            ->first();

        if (! $employee || ! Hash::check($validated['password'], $employee->password)) {
            return response()->json([
                'message' => 'Invalid employee credentials.',
            ], 422);
        }

        $token = $employee->createToken('employee-panel');

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token->plainTextToken,
            'employee' => $employee,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $accessToken = $request->attributes->get('employee_access_token');

        if ($accessToken) {
            $accessToken->delete();
        }

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }
}

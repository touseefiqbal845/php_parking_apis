<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Laravel\Passport\HasApiTokens;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Attempt to authenticate user using email or username
        $user = User::where('email', $request->username)
                    ->orWhere('username', $request->username)
                    ->first();

        if (!$user || !Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate access token using Passport
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $tokenResult->token->expires_at,
        ]);
    }

    public function logout(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();
        dd($user);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Revoke the user's current access token
        Auth::logout();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }
}

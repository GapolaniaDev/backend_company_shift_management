<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * Controller for authentication functions
 */

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="User registration, login and session management endpoints"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     description="Creates a new user in the system",
     *     operationId="registerUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Smith", description="Full name of the user"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Password (minimum 8 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Password confirmation"),
     *             @OA\Property(property="role", type="string", enum={"admin", "supervisor", "employee"}, example="employee", description="User role (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User successfully registered",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User successfully registered")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email has already been taken")),
     *             @OA\Property(property="password", type="array", @OA\Items(type="string", example="The password must be at least 8 characters"))
     *         )
     *     )
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string', 
                'email',
                'max:255',
                Rule::unique('users')->where(fn($q) => $q->where('company_id', app('currentCompanyId', 1)))
            ],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|string|in:admin,supervisor,employee',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role ?? 'employee',
        ]);

        return response()->json(['message' => 'User successfully registered'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login to the system",
     *     description="Authenticates a user and returns an access token",
     *     operationId="loginUser",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email address"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Password"),
     *             @OA\Property(property="extended_token", type="boolean", example=false, description="Request a long-lived token (24h instead of 1h)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful."),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="expires_in", type="integer", example=3600, description="Token expiration time in seconds"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Smith"),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="role", type="string", example="employee")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials. Please check your email and password.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation errors occurred."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required")),
     *                 @OA\Property(property="password", type="array", @OA\Items(type="string", example="The password field is required"))
     *             )
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        
        // Add company_id filter if current company is set
        if (app()->bound('currentCompanyId') && app('currentCompanyId')) {
            $credentials['company_id'] = app('currentCompanyId');
        }

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please check your email and password.',
            ], 401);
        }

        $user = Auth::user();
        $tokenExpiration = $request->input('extended_token', false) ? 1440 : 60; // 24h or 1h
        $token = $user->createToken('Personal Access Token', ['*'], now()->addMinutes($tokenExpiration))->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'expires_in' => $tokenExpiration * 60, // in seconds
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout from the system",
     *     description="Revokes the user's access token",
     *     operationId="logoutUser",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        $user->token()->revoke();
        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get authenticated user information",
     *     description="Returns current user data and employee profile if it exists",
     *     operationId="getCurrentUser",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Smith"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="role", type="string", example="employee"),
     *             @OA\Property(
     *                 property="employee",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Smith"),
     *                 @OA\Property(property="full_name", type="string", example="John Smith"),
     *                 @OA\Property(property="email", type="string", example="john@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $employee = null;

        if ($user->employee) {
            $employee = [
                'id' => $user->employee->id,
                'first_name' => $user->employee->first_name,
                'last_name' => $user->employee->last_name,
                'full_name' => $user->employee->full_name,
                'email' => $user->employee->email,
            ];
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'employee' => $employee,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/refresh-token",
     *     summary="Refresh token",
     *     description="Revokes current tokens and generates a new one",
     *     operationId="refreshToken",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="extended_token", type="boolean", example=false, description="Request a long-lived token (24h instead of 1h)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token refreshed successfully."),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="expires_in", type="integer", example=3600, description="Token expiration time in seconds")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete(); // Revoke all tokens

        $tokenExpiration = $request->input('extended_token', false) ? 1440 : 60; // 24h or 1h
        $token = $user->createToken('Personal Access Token', ['*'], now()->addMinutes($tokenExpiration))->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully.',
            'token' => $token,
            'expires_in' => $tokenExpiration * 60, // in seconds
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $this->validateRequest($request, [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
                'device_name' => 'sometimes|string|max:255',
            ]);

            // 使用AdminUser进行认证
            $user = AdminUser::where('email', $credentials['email'])->first();

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return $this->unauthorizedResponse('Invalid credentials');
            }

            if (!$user->isActive()) {
                return $this->forbiddenResponse('Account is disabled');
            }

            // 创建token
            $deviceName = $credentials['device_name'] ?? $request->userAgent();
            $token = $user->createToken($deviceName)->plainTextToken;

            // 记录登录
            $this->logApiRequest($request, 'login');
            do_action('api.user.login', $user, $request);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'is_super_admin' => $user->is_super_admin,
                    'created_at' => $user->created_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_at' => null, // Sanctum tokens don't expire by default
            ], 'Login successful');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            // 删除当前访问token
            $request->user()->currentAccessToken()->delete();
            
            do_action('api.user.logout', $request->user(), $request);
            $this->logApiRequest($request, 'logout');

            return $this->successResponse(null, 'Logout successful');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->status,
                    'is_super_admin' => $user->is_super_admin,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'permissions' => $user->getPermissionNames(),
                'token_name' => $request->user()->currentAccessToken()->name,
                'token_created' => $request->user()->currentAccessToken()->created_at,
            ], 'User information retrieved');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get user information: ' . $e->getMessage(), 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentToken = $request->user()->currentAccessToken();
            
            // 删除旧token
            $currentToken->delete();
            
            // 创建新token
            $deviceName = $currentToken->name ?: $request->userAgent();
            $newToken = $user->createToken($deviceName)->plainTextToken;

            do_action('api.token.refreshed', $user, $request);

            return $this->successResponse([
                'token' => $newToken,
                'token_type' => 'Bearer',
                'expires_at' => null,
            ], 'Token refreshed successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }

    public function revokeAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // 删除用户的所有tokens
            $user->tokens()->delete();
            
            do_action('api.tokens.revoked_all', $user, $request);

            return $this->successResponse(null, 'All tokens revoked successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke tokens: ' . $e->getMessage(), 500);
        }
    }

    public function tokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tokens = $user->tokens()->select(['id', 'name', 'created_at', 'last_used_at'])->get();
            
            return $this->successResponse([
                'tokens' => $tokens,
                'current_token_id' => $request->user()->currentAccessToken()->id,
            ], 'Tokens retrieved successfully');
            
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to get tokens: ' . $e->getMessage(), 500);
        }
    }

    public function revokeToken(Request $request): JsonResponse
    {
        try {
            $credentials = $this->validateRequest($request, [
                'token_id' => 'required|integer|exists:personal_access_tokens,id',
            ]);
            
            $user = $request->user();
            $token = $user->tokens()->where('id', $credentials['token_id'])->first();
            
            if (!$token) {
                return $this->notFoundResponse('Token');
            }
            
            $token->delete();
            
            do_action('api.token.revoked', $user, $token, $request);

            return $this->successResponse(null, 'Token revoked successfully');
            
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to revoke token: ' . $e->getMessage(), 500);
        }
    }
}
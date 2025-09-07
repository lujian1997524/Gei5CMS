<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Gei5CMS API 文档",
 *      description="Gei5CMS RESTful API 接口文档",
 *      termsOfService="https://gei5cms.com/terms",
 *      @OA\Contact(
 *          email="support@gei5cms.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Gei5CMS API Server"
 * )
 *
 * @OA\SecurityScheme(
 *      securityScheme="sanctum",
 *      type="http",
 *      scheme="bearer",
 *      bearerFormat="JWT",
 *      description="Use Bearer token from /api/v1/auth/login endpoint"
 * )
 *
 * @OA\Tag(
 *      name="Authentication",
 *      description="身份认证接口"
 * )
 *
 * @OA\Tag(
 *      name="Plugins",
 *      description="插件管理接口"
 * )
 *
 * @OA\Tag(
 *      name="Themes",
 *      description="主题管理接口"
 * )
 *
 * @OA\Tag(
 *      name="Users",
 *      description="用户管理接口"
 * )
 *
 * @OA\Tag(
 *      name="Settings",
 *      description="系统设置管理接口"
 * )
 *
 * @OA\Tag(
 *      name="System",
 *      description="系统信息和工具"
 * )
 */
class SwaggerController extends BaseApiController
{
    /**
     * @OA\Get(
     *      path="/api/health",
     *      operationId="healthCheck",
     *      tags={"System"},
     *      summary="系统健康检查",
     *      description="检查系统运行状态和基本信息",
     *      @OA\Response(
     *          response=200,
     *          description="请求成功",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="healthy", description="系统状态"),
     *              @OA\Property(property="version", type="string", example="1.0.0", description="系统版本"),
     *              @OA\Property(property="timestamp", type="string", format="datetime", description="响应时间"),
     *              @OA\Property(property="system", type="object", description="系统信息",
     *                  @OA\Property(property="php_version", type="string", example="8.2.0", description="PHP版本"),
     *                  @OA\Property(property="laravel_version", type="string", example="12.0.0", description="Laravel版本")
     *              )
     *          )
     *      )
     * )
     */

    /**
     * @OA\Get(
     *      path="/api/info",
     *      operationId="apiInfo",
     *      tags={"System"},
     *      summary="API information",
     *      description="Returns API version and general information",
     *      @OA\Response(
     *          response=200,
     *          description="Successful response",
     *          @OA\JsonContent(
     *              @OA\Property(property="api_version", type="string", example="v1"),
     *              @OA\Property(property="name", type="string", example="Gei5CMS"),
     *              @OA\Property(property="description", type="string", example="Gei5CMS RESTful API"),
     *              @OA\Property(property="documentation", type="string", format="url"),
     *              @OA\Property(property="contact", type="object",
     *                  @OA\Property(property="support", type="string", example="support@gei5cms.com"),
     *                  @OA\Property(property="website", type="string", example="https://gei5cms.com")
     *              )
     *          )
     *      )
     * )
     */

    /**
     * @OA\Post(
     *      path="/api/v1/auth/login",
     *      operationId="login",
     *      tags={"Authentication"},
     *      summary="用户登录",
     *      description="用户身份验证并返回API访问令牌",
     *      @OA\RequestBody(
     *          required=true,
     *          description="登录信息",
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="admin@gei5cms.local", description="用户邮箱"),
     *              @OA\Property(property="password", type="string", format="password", example="password123", description="用户密码"),
     *              @OA\Property(property="device_name", type="string", example="Web Browser", description="设备名称（可选）")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="登录成功",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true, description="请求是否成功"),
     *              @OA\Property(property="message", type="string", example="Login successful", description="响应消息"),
     *              @OA\Property(property="version", type="string", example="v1", description="API版本"),
     *              @OA\Property(property="timestamp", type="string", format="datetime", description="响应时间"),
     *              @OA\Property(property="data", type="object", description="返回数据",
     *                  @OA\Property(property="user", type="object", description="用户信息",
     *                      @OA\Property(property="id", type="integer", example=1, description="用户ID"),
     *                      @OA\Property(property="name", type="string", example="admin", description="用户名"),
     *                      @OA\Property(property="email", type="string", example="admin@gei5cms.local", description="邮箱"),
     *                      @OA\Property(property="status", type="string", example="active", description="用户状态"),
     *                      @OA\Property(property="is_super_admin", type="boolean", example=true, description="是否超级管理员")
     *                  ),
     *                  @OA\Property(property="token", type="string", example="1|abcdef123456...", description="访问令牌"),
     *                  @OA\Property(property="token_type", type="string", example="Bearer", description="令牌类型"),
     *                  @OA\Property(property="expires_at", type="string", nullable=true, description="过期时间")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="登录失败",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Invalid credentials", description="错误信息")
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="账户被禁用",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Account is disabled", description="错误信息")
     *          )
     *      )
     * )
     */

    /**
     * @OA\Post(
     *      path="/api/v1/auth/logout",
     *      operationId="logout",
     *      tags={"Authentication"},
     *      summary="User logout",
     *      description="Revoke current API token",
     *      security={{"sanctum": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="Logout successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Logout successful")
     *          )
     *      )
     * )
     */

    /**
     * @OA\Get(
     *      path="/api/v1/auth/user",
     *      operationId="getAuthUser",
     *      tags={"Authentication"},
     *      summary="Get authenticated user",
     *      description="Returns current authenticated user information",
     *      security={{"sanctum": {}}},
     *      @OA\Response(
     *          response=200,
     *          description="User information retrieved",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="user", type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="admin"),
     *                      @OA\Property(property="email", type="string", example="admin@gei5cms.local"),
     *                      @OA\Property(property="status", type="string", example="active"),
     *                      @OA\Property(property="is_super_admin", type="boolean", example=true)
     *                  ),
     *                  @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *                  @OA\Property(property="token_name", type="string", example="Web Browser")
     *              )
     *          )
     *      )
     * )
     */

    public function index(): JsonResponse
    {
        return $this->successResponse([
            'message' => 'Gei5CMS API Documentation',
            'version' => 'v1.0.0',
            'swagger_url' => url('/api/documentation'),
        ], 'API documentation information');
    }
}
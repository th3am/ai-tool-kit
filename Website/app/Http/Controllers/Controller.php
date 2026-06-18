<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="EduAI Platform API",
 *      description="API Documentation for EduAI Platform",
 *      @OA\Contact(
 *          email="support@eduai.com"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 *
 * @OA\Server(
 *      url="/",
 *      description="Primary API Server"
 * )
 * 
 * @OA\Tag(name="1. Auth", description="Authentication & CSRF"),
 * @OA\Tag(name="2. User", description="User Profile & Settings"),
 * @OA\Tag(name="3. Sessions", description="Chat Sessions Context"),
 * @OA\Tag(name="4. Tools", description="AI Generation Tools"),
 * @OA\Tag(name="5. Jobs", description="Background Job Status"),
 * @OA\Tag(name="6. Downloads", description="File Downloads"),
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Token based Based",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth",
 * )
 *
 * @OA\SecurityScheme(
 *     type="apiKey",
 *     in="cookie",
 *     name="laravel_session",
 *     securityScheme="sanctum",
 *     description="Session Cookie Authentication"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}

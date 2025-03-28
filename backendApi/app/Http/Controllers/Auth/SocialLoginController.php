<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Google_Client;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


class SocialLoginController extends Controller
{
    public function handleGoogleLogin(Request $request)
    {
        $idToken = $request->input('access_token');
    
        $client = new \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]); // 你的前端 Client ID
        $payload = $client->verifyIdToken($idToken);
    
        if (!$payload) {
            return response()->json(['message' => 'Invalid Google ID token'], 401);
        }
    
        $email = strtolower($payload['email']);
        $name = $payload['name'] ?? 'Google User';
    
        // 查詢或建立使用者
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => bcrypt(Str::random(12))]
        );
    
        // 建立 Employee（如果你有關聯）
        if (!$user->employee) {
            Employee::create([
                'user_id' => $user->id,
                'status' => 'pending',
            ]);
        }
    
        $token = JWTAuth::fromUser($user);
    
        return response()->json([
            'access_token' => $token,
            'user' => $user,
        ]);
    }
}

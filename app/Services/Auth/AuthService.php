<?php

namespace App\Services\Auth;

use App\Http\Resources\Role\RoleResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\UserRolePremission\UserPermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthService
{
    protected $userPermissionService;

    public function __construct(
        UserPermissionService $userPermissionService,
    )
    {
        $this->userPermissionService = $userPermissionService;
    }

    public function register(array $data)
    {
        try {
            $user = User::create([
                'name'=> $data['name'],
                'surname'=> $data['surname'],
                'email'=> $data['email'],
                'password'=> Hash::make($data['password']),
                'gender' => $data['gender'],
                'user_type' => $data['userType'],
            ]);

            return response()->json([
                'message' => 'user has been created!'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function login(array $data)
    {
        try {
            $userToken = Auth::attempt([
                'username' => $data['username'],
                'password' => $data['password']
            ]);

            if (!$userToken) {
                return response()->json([
                    'message' => 'Nome utente o password non validi!',
                ], 401);
            }

            if (Auth::user()->status->value == 0) {
                Auth::logout();

                return response()->json([
                    'message' => 'Questo account non e attivo!',
                ], 401);
            }

            return $this->buildAuthResponse($userToken);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function refresh(?string $refreshToken)
    {
        try {
            if (!$refreshToken) {
                return response()->json([
                    'message' => 'refreshToken is required',
                ], 422);
            }

            $newToken = Auth::setToken($refreshToken)->refresh();

            return $this->buildAuthResponse($newToken);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Invalid or expired refresh token',
            ], 401);
        }
    }

    public function logout()
    {
        Auth::logout();

        return response()->json(['message' => 'you have logged out']);
    }

    protected function buildAuthResponse(string $userToken)
    {
        Auth::setToken($userToken);

        $user = Auth::user();

        if (!$user || $user->status->value == 0) {
            return response()->json([
                'message' => 'Questo account non e attivo!',
            ], 401);
        }

        $userRoles = $user->getRoleNames();
        $role = Role::findByName($userRoles[0]);
        $expiresIn = Auth::factory()->getTTL() * 60 * 1000;

        return response()->json([
            'token' => $userToken,
            'refreshToken' => $userToken,
            'expiresIn' => $expiresIn,
            'profile' => new UserResource($user),
            'role' => new RoleResource($role),
            'permissions' => $this->userPermissionService->getUserPermissions($user),
        ], 200)->header('Authorization', $userToken);
    }
}

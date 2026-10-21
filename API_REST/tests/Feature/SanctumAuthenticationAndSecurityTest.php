<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas integrales de seguridad, autenticación stateless mediante Laravel Sanctum,
 * emisión de tokens Bearer, protección de endpoints privados y revocación de sesión.
 */
class SanctumAuthenticationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_issue_sanctum_bearer_token_and_user_payload(): void
    {
        User::create([
            'username' => 'operador_caja',
            'password' => Hash::make('ClaveSegura2026*'),
            'role' => 'Admin',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'operador_caja',
            'password' => 'ClaveSegura2026*',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user' => ['id', 'username', 'role'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.username', 'operador_caja');
    }

    public function test_invalid_password_returns_unauthorized_error_without_token(): void
    {
        User::create([
            'username' => 'admin_taller',
            'password' => Hash::make('PasswordCorrecto123'),
            'role' => 'Admin',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'admin_taller',
            'password' => 'PasswordIncorrecto999',
        ]);

        $response->assertStatus(401);
    }

    public function test_protected_pos_and_inventory_routes_reject_unauthenticated_requests(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/products'],
            ['GET', '/api/customers'],
            ['GET', '/api/vehicles'],
            ['GET', '/api/sales'],
            ['GET', '/api/schedules'],
            ['GET', '/api/workers'],
            ['GET', '/api/expenses'],
            ['GET', '/api/suppliers'],
            ['GET', '/api/analytics/summary'],
        ];

        foreach ($protectedEndpoints as [$method, $uri]) {
            $this->json($method, $uri)->assertStatus(401);
        }
    }

    public function test_logout_revokes_active_personal_access_token(): void
    {
        $user = User::create([
            'username' => 'cajero_turno',
            'password' => Hash::make('Turno123456'),
            'role' => 'Admin',
        ]);

        $token = $user->createToken('pos-session')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}

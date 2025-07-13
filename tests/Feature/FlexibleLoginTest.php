<?php

use App\Models\User;
use App\Models\Role;

beforeEach(function () {
    $this->seed();
});

test('user can login with email', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => null,
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email_or_username' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('user can login with username', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email_or_username' => 'testuser',
        'password' => 'password',
    ]);

    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
});

test('login fails with invalid credentials', function () {
    $response = $this->post('/login', [
        'email_or_username' => 'nonexistent@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors(['email_or_username']);
    $this->assertGuest();
});

test('login fails for inactive user', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->create([
        'email' => 'inactive@example.com',
        'password' => bcrypt('password'),
        'role_id' => $role->id,
        'is_active' => false,
    ]);

    $response = $this->post('/login', [
        'email_or_username' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('username must be unique', function () {
    $role = Role::where('name', 'admin')->first();
    User::factory()->create([
        'username' => 'uniqueuser',
        'role_id' => $role->id,
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);
    
    User::factory()->create([
        'username' => 'uniqueuser',
        'role_id' => $role->id,
    ]);
});

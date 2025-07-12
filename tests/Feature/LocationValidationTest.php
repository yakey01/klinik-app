<?php

use App\Models\LocationValidation;
use App\Models\User;

test('location validation can be created with proper relationships', function () {
    $user = User::factory()->create();

    $validation = LocationValidation::factory()
        ->for($user)
        ->withinZone()
        ->create([
            'attendance_type' => 'check_in',
            'notes' => 'Test validation'
        ]);

    expect($validation)->toBeInstanceOf(LocationValidation::class);
    expect($validation->user_id)->toBe($user->id);
    expect($validation->is_within_zone)->toBeTrue();
    expect($validation->attendance_type)->toBe('check_in');
    expect($validation->notes)->toBe('Test validation');

    // Test relationship
    expect($validation->user->id)->toBe($user->id);
    expect($validation->user->name)->toBe($user->name);
});

test('distance calculation between coordinates works correctly', function () {
    // Test distance between Jakarta and Bandung
    $jakartaLat = -6.2088;
    $jakartaLon = 106.8456;
    $bandungLat = -6.9175;
    $bandungLon = 107.6191;

    $distance = LocationValidation::calculateDistance(
        $jakartaLat, $jakartaLon, 
        $bandungLat, $bandungLon
    );

    // Distance Jakarta to Bandung is approximately 116km
    expect($distance)->toBeGreaterThan(115000) // Greater than 115km
                    ->toBeLessThan(120000);    // Less than 120km
});

test('location validation method works for within and outside zone', function () {
    $officeLat = -6.2088;
    $officeLon = 106.8456;
    $radius = 100; // 100 meters

    // Test user within work zone
    $userNearLat = -6.2089;  // Very close to office
    $userNearLon = 106.8457;
    
    $validation = LocationValidation::validateLocation(
        $userNearLat, $userNearLon,
        $officeLat, $officeLon,
        $radius
    );

    expect($validation['is_within_zone'])->toBeTrue();
    expect($validation['distance_from_zone'])->toBe(0);
    expect($validation['actual_distance'])->toBeLessThan($radius);

    // Test user outside work zone
    $userFarLat = -6.2200;  // Much farther from office
    $userFarLon = 106.8600;
    
    $validationFar = LocationValidation::validateLocation(
        $userFarLat, $userFarLon,
        $officeLat, $officeLon,
        $radius
    );

    expect($validationFar['is_within_zone'])->toBeFalse();
    expect($validationFar['distance_from_zone'])->toBeGreaterThan(0);
    expect($validationFar['actual_distance'])->toBeGreaterThan($radius);
});

test('model scopes filter data correctly', function () {
    $user = User::factory()->create();

    // Create validations within zone
    LocationValidation::factory()->for($user)->withinZone()->count(5)->create();
    // Create validations outside zone
    LocationValidation::factory()->for($user)->outsideZone()->count(3)->create();
    
    // Create additional check-in and check-out validations (they'll be randomly within/outside)
    LocationValidation::factory()->for($user)->checkIn()->count(2)->create();
    LocationValidation::factory()->for($user)->checkOut()->count(2)->create();

    // Test zone scopes - we know exactly how many we created
    expect(LocationValidation::valid()->count())->toBeGreaterThanOrEqual(5);
    expect(LocationValidation::invalid()->count())->toBeGreaterThanOrEqual(3);
    
    // Test attendance type scopes
    expect(LocationValidation::checkIn()->count())->toBeGreaterThanOrEqual(2);
    expect(LocationValidation::checkOut()->count())->toBeGreaterThanOrEqual(2);
});

test('model attributes and casts work correctly', function () {
    $validation = LocationValidation::factory()
        ->withinZone()
        ->create([
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'distance_from_zone' => 0,
            'attendance_type' => 'check_in'
        ]);

    // Test attribute accessors
    expect($validation->validation_status_color)->toBe('success');
    expect($validation->validation_status_label)->toBe('✅ Dalam Area');
    expect($validation->attendance_type_label)->toBe('📥 Check In');
    expect($validation->google_maps_url)->toContain('maps.google.com');
    expect($validation->distance_from_zone_formatted)->toBe('0.0 m');

    // Test casts (decimal casts return strings in Laravel)
    expect($validation->latitude)->toBeString();
    expect($validation->longitude)->toBeString();
    expect($validation->is_within_zone)->toBeBool();
    expect($validation->validation_time)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('validation summary provides correct statistics', function () {
    $user = User::factory()->create();

    // Create test data
    LocationValidation::factory()->for($user)->withinZone()->count(7)->create();
    LocationValidation::factory()->for($user)->outsideZone()->count(3)->create();
    LocationValidation::factory()->for($user)->checkIn()->count(5)->create();
    LocationValidation::factory()->for($user)->checkOut()->count(5)->create();

    $summary = LocationValidation::getValidationSummary();

    expect($summary)->toBeArray()
                   ->toHaveKeys(['total', 'valid', 'invalid', 'check_ins', 'check_outs', 'success_rate']);

    expect($summary['total'])->toBeGreaterThan(0);
    expect($summary['success_rate'])->toBeFloat();
});

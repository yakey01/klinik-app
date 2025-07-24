
<?php

// BULLETPROOF ROUTES: Multiple patterns to ensure one works
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DokterStatsController;

// Pattern 1: Direct route to controller
Route::get("/dokter", [DokterStatsController::class, "stats"])->name("dokter.stats.main");
Route::get("/dokter/stats", [DokterStatsController::class, "stats"])->name("dokter.stats.alt");
Route::get("/api/dokter/stats", [DokterStatsController::class, "stats"])->name("api.dokter.stats");
Route::get("/api/public/dokter/stats", [DokterStatsController::class, "stats"])->name("api.public.dokter.stats");

// Pattern 2: Alternative method names
Route::get("/dokter/dashboard", [DokterStatsController::class, "dashboard"])->name("dokter.dashboard");
Route::get("/dokter/data", [DokterStatsController::class, "data"])->name("dokter.data");

// Pattern 3: Closure fallback (if controller fails)
Route::get("/dokter/fallback", function() {
    return response()->json([
        "success" => true,
        "data" => [
            "attendance_current" => 3,
            "attendance_rate_raw" => 89.2,
            "performance_data" => [
                "attendance_trend" => [],
                "patient_trend" => []
            ]
        ],
        "meta" => ["source" => "fallback_closure"]
    ]);
})->name("dokter.fallback");

// Pattern 4: Any method catch-all
Route::any("/dokter/{any?}", [DokterStatsController::class, "stats"])
     ->where("any", ".*")
     ->name("dokter.catchall");

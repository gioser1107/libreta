<?php

use App\Livewire\Actions\Logout;
use App\Livewire\CurrencyCalculator;
use App\Livewire\ExpenseLedger;
use App\Livewire\IncomeLedger;
use App\Livewire\MonthSummary;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/manifest.webmanifest', function () {
    $name = (string) config('libreta.company');

    return response()->json([
        'id' => '/',
        'name' => $name,
        'short_name' => $name,
        'description' => 'Ingresos y egresos',
        'lang' => 'es',
        'dir' => 'ltr',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'display_override' => ['standalone', 'minimal-ui'],
        'orientation' => 'any',
        'background_color' => '#ffffff',
        'theme_color' => '#ffffff',
        'categories' => ['finance', 'productivity'],
        'prefer_related_applications' => false,
        'launch_handler' => [
            'client_mode' => 'navigate-existing',
        ],
        'icons' => [
            [
                'src' => '/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-maskable-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ], 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'no-cache',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->name('pwa.manifest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', MonthSummary::class)
        ->middleware(['permission:ingresos.view', 'permission:egresos.view'])
        ->name('dashboard');

    Route::get('/ingresos', IncomeLedger::class)
        ->middleware('permission:ingresos.view')
        ->name('incomes.index');

    Route::get('/egresos', ExpenseLedger::class)
        ->middleware('permission:egresos.view')
        ->name('expenses.index');

    Route::get('/calculadora', CurrencyCalculator::class)->name('calculator');

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Logout $logout) {
        $logout();

        return redirect('/');
    })->name('logout');
});

require __DIR__.'/auth.php';

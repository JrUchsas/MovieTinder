<?php

use App\Http\Controllers\SwipeController;
use App\Models\Movie;
use App\Models\Swipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function (Request $request) {
    $userId = $request->user()->id;

    $totalSwipes = Swipe::where('user_id', $userId)->count();
    $totalLiked = Swipe::where('user_id', $userId)->where('is_liked', true)->count();
    $totalPassed = Swipe::where('user_id', $userId)->where('is_liked', false)->count();

    $recentWatchlist = Movie::whereHas('swipes', function ($query) use ($userId): void {
        $query->where('user_id', $userId)->where('is_liked', true);
    })->latest()->take(4)->get();

    return Inertia::render('Dashboard', [
        'stats' => [
            'total_swipes' => $totalSwipes,
            'total_liked' => $totalLiked,
            'total_passed' => $totalPassed,
        ],
        'recentWatchlist' => $recentWatchlist,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/swipe', [SwipeController::class, 'index'])->name('swipe.index');
    Route::post('/swipe', [SwipeController::class, 'store'])->name('swipe.store');
    Route::get('/watchlist', [SwipeController::class, 'watchlist'])->name('watchlist.index');
    Route::patch('/watchlist/{movie}', [SwipeController::class, 'update'])->name('watchlist.update');
    Route::patch('/watchlist/{movie}/watch-status', [SwipeController::class, 'updateWatchStatus'])->name('watchlist.watch-status');
    Route::delete('/watchlist/{movie}', [SwipeController::class, 'destroy'])->name('watchlist.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

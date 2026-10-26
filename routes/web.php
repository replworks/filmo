<?php

use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\PublicLandingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', PublicLandingController::class)->name('home');

Route::get('/catalog', [PublicCatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/channels/{channel}', [PublicCatalogController::class, 'channel'])->name('catalog.channels.show');
Route::get('/catalog/videos/{video}', [PublicCatalogController::class, 'video'])->name('catalog.videos.show');
Route::get('/catalog/actors/{actor}', [PublicCatalogController::class, 'actor'])->name('catalog.actors.show');

Route::get('/contributor', fn () => Inertia::render('contributor/index'))
    ->middleware('auth')
    ->name('contributor');

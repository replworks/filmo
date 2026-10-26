<?php

use App\Http\Controllers\ContributorWorkspaceController;
use App\Http\Controllers\PublicCatalogController;
use App\Http\Controllers\PublicLandingController;
use Illuminate\Support\Facades\Route;

Route::get('/', PublicLandingController::class)->name('home');

Route::get('/catalog', [PublicCatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/channels/{channel}', [PublicCatalogController::class, 'channel'])->name('catalog.channels.show');
Route::get('/catalog/videos/{video}', [PublicCatalogController::class, 'video'])->name('catalog.videos.show');
Route::get('/catalog/actors/{actor}', [PublicCatalogController::class, 'actor'])->name('catalog.actors.show');

Route::middleware('auth')->prefix('contributor')->name('contributor.')->group(function (): void {
    Route::get('/', [ContributorWorkspaceController::class, 'index'])->name('index');
    Route::post('/channels', [ContributorWorkspaceController::class, 'storeChannel'])->name('channels.store');
    Route::post('/videos', [ContributorWorkspaceController::class, 'storeVideo'])->name('videos.store');
});

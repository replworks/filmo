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
    Route::get('/channels/{channel}/edit', [ContributorWorkspaceController::class, 'editChannel'])->name('channels.edit');
    Route::patch('/channels/{channel}', [ContributorWorkspaceController::class, 'updateChannel'])->name('channels.update');
    Route::post('/videos', [ContributorWorkspaceController::class, 'storeVideo'])->name('videos.store');
    Route::get('/videos/{video}/edit', [ContributorWorkspaceController::class, 'editVideo'])->name('videos.edit');
    Route::patch('/videos/{video}', [ContributorWorkspaceController::class, 'updateVideo'])->name('videos.update');
    Route::post('/actors', [ContributorWorkspaceController::class, 'storeActor'])->name('actors.store');
    Route::get('/actors/{actor}/edit', [ContributorWorkspaceController::class, 'editActor'])->name('actors.edit');
    Route::patch('/actors/{actor}', [ContributorWorkspaceController::class, 'updateActor'])->name('actors.update');
    Route::post('/actors/{actor}/videos', [ContributorWorkspaceController::class, 'attachVideo'])->name('actors.videos.store');
    Route::delete('/actors/{actor}/videos/{video}', [ContributorWorkspaceController::class, 'detachVideo'])->name('actors.videos.destroy');
});

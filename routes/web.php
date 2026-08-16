<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RepositorySubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('lang.switch');

Route::post('/repositories', [RepositorySubmissionController::class, 'store'])
    ->middleware('throttle:repository-analysis')
    ->name('repositories.submit');

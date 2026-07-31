<?php

use App\Http\Controllers\RepositorySubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/repositories', [RepositorySubmissionController::class, 'store'])
    ->middleware('throttle:repository-analysis')
    ->name('repositories.submit');

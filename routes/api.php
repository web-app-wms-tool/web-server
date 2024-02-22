<?php

use App\Http\Controllers\Api\ConvertedFileController;
use App\Http\Controllers\Api\UploadedFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

Route::group([], function () {
    Route::post('converted-file-list', [ConvertedFileController::class, 'indexAgGrid']);

    Route::post('uploaded-file-list', [UploadedFileController::class, 'indexAgGrid']);
    Route::apiResource('uploaded-files', UploadedFileController::class)->only(['store', 'destroy']);
    Route::post('uploaded-file-list/{id}/convert', [UploadedFileController::class, 'convert']);
});

<?php

use Illuminate\Support\Facades\Route;
use Weap\Junction\Http\Controllers\MediaTemporaryUploadController;

Route::post('media/upload', [MediaTemporaryUploadController::class, 'upload']);

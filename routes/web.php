<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('image')->name('image.')->group(function(){
    Route::post('/store', [ImageController::class,'store'])->name('store');
    Route::get('/image/download', [ImageController::class, 'download'])->name('download');
    Route::post('/delete', [ImageController::class,'deleteBase64Session'])->name('base64session');

});
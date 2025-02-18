<?php

use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('file',[VideoController::class, 'upload'])->name('video.upload');
Route::get('/video/{videoId}/master.m3u8', [VideoController::class, 'getMasterPlaylist']);
Route::get('/video/{videoId}/{file}/{ext?}', [VideoController::class, 'getHlsFile'])->name('get.hls.file');
Route::get('/upload/{index}/{localPath?}', [VideoController::class, 'uploadToR2']);

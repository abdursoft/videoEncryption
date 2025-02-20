<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use AWS\CRT\HTTP\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/video/{videoId}/master.m3u8', [VideoController::class, 'getMasterPlaylist']);
Route::get('/videos/{videoId}/{file}/{ext?}', [VideoController::class, 'getHlsFile'])->name('get.hls.file');

Route::get('player/{key}',[VideoController::class, 'player'])->name('player');

Route::get('/dashboard', [VideoController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::get('/upload-list',[VideoController::class, 'index'])->name('video.list');
    Route::get('/upload-video',[VideoController::class, 'create'])->name('video.form');
    Route::get('/upload/{index}/{localPath?}', [VideoController::class, 'uploadToR2'])->name('r2.upload');
    Route::post('/file-upload',[VideoController::class, 'upload'])->name('video.upload');
    Route::get('/file-delete/{index}',[VideoController::class, 'deleteFromLocal'])->name('file.remove');
    Route::get('/file-remove/{index}',[VideoController::class, 'deleteFromR2'])->name('r2.remove');
});
require __DIR__.'/auth.php';

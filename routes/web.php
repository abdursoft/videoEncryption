<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\VideoRouteController;
use App\Http\Middleware\AuthTokenMiddleware;
use App\Http\Middleware\BlockDownloadExtensions;
use AWS\CRT\HTTP\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/video/{videoId}/master.m3u8', [VideoController::class, 'getMasterPlaylist'])->middleware(AuthTokenMiddleware::class);
Route::get('/videos/{videoId}/{file}/{ext?}', [VideoController::class, 'getM3U8Signed'])->name('get.hls.file');

Route::get('player/{key}',[VideoController::class, 'player'])->name('player');


Route::get('/s3', [VideoController::class, 'getObjects']);
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

Route::get('/master/{file}', [VideoRouteController::class, 'getSignedMasterM3U8']);
// Route::get('/video/signed-resolution/{resolution}/{file}', [VideoRouteController::class, 'getSignedResolutionM3U8']);
// Route::get('/video/signed-segment/{resolution}/{file}', [VideoRouteController::class, 'getSignedSegment']);
// Route::get('/video/signed-key/{key}', [VideoRouteController::class, 'getSignedKey']);
Route::get('/video/{file}', [VideoRouteController::class, 'getM3U8Signed']);
// Route::get('/hls/{resolution}/{file}', [VideoRouteController::class, 'getIndexM3U8Signed'])->middleware(BlockDownloadExtensions::class);

Route::get('/stream', function () {

    // Generate a temporary URL from iDrive E2
    $idriveUrl = "https://h7j8.c13.e2-5.dev/sg-ch/67d512bbe7890/md/index.m3u8";

    // Stream the video through Laravel
    return response()->stream(function () use ($idriveUrl) {
        readfile($idriveUrl);
    }, 200, [
        "Content-Type" => "video/mp2t",
        "Cache-Control" => "no-cache, no-store, must-revalidate",
        "Pragma" => "no-cache",
        "Expires" => "0",
    ]);
});

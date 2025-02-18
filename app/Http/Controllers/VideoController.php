<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoReverb;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mkv,avi|max:102400',
        ]);

        // Store the uploaded file in public storage
        $filePath = $request->file('video')->store('videos', 'public');

        // Generate a unique ID for HLS processing
        $index = uniqid();

        // Generation encryption and iv key
        $keyPath     = storage_path("app/public/hls/$index/key/enc.key");
        $keyInfoPath = storage_path("app/public/hls/$index/key/enc.keyinfo");

        // Generate a random 16-byte AES key
        $key = openssl_random_pseudo_bytes(16);
        Storage::disk('public')->put("hls/$index/key/enc.key", $key);

        // Generate a correct 16-byte (32 hex character) IV
        $iv = bin2hex(random_bytes(16)); // 32 hex characters

        // Create the keyinfo file
        if (! empty($request->input('domain'))) {
            $keyUri = trim($request->input('domain'), '/') . "/$index/key/enc.key";
        } else {
            $keyUri = $request->root() . Storage::url("hls/$index/key/enc.key");
        }
        Storage::disk('public')->put("hls/$index/key/enc.keyinfo", "$keyUri\n$keyPath\n$iv");

        // Get video duration
        $duration = $this->getVideoDuration(storage_path("app/public/{$filePath}"));

        // Dispatch the processing job with file path instead of UploadedFile object
        ProcessVideoReverb::dispatch(storage_path("app/public/{$filePath}"), $index, $keyInfoPath);

        return response()->json(['message' => 'Video is processing.']);
    }

    public function getMasterPlaylist($videoId)
    {
        $disk = Storage::disk('r2');
        $masterPlaylistPath = "{$videoId}/master.m3u8";

        if (!$disk->exists($masterPlaylistPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $masterContent = $disk->get($masterPlaylistPath);

        // Replace relative paths with Laravel routes
        $modifiedMaster = preg_replace_callback('/^(low|mid|high|fullHd|2k|4k|8k|key)\/index\.m3u8|.*\/enc\.key$/m', function ($matches) use ($videoId) {
            return route('get.hls.file', ['videoId' => $videoId, 'file' => $matches[0]]);
        }, $masterContent);

        return response($modifiedMaster)->header("Content-Type", "application/x-mpegURL");
    }

    public function getHlsFile($videoId, $file,$ext=null)
    {
        $disk = Storage::disk('r2');
        $filePath = "{$videoId}/{$file}/{$ext}";

        if (!$disk->exists($filePath)) {
            return response()->json(['error' => 'File not found'], 404);
        }
        return $disk->get($filePath);

        return response($disk->get($filePath))
        ->header("Content-Type", $file == 'enc.key' ? "application/octet-stream" : "application/x-mpegURL");
    }

    public function uploadToR2($index, $localPath = null)
    {
        // Set the initial path if not provided
        if ($localPath === null) {
            $localPath = storage_path("app/public/hls/$index");
        }

        // Scan the directory for files and subdirectories
        $files = scandir($localPath);

        foreach ($files as $file) {
            // Skip the current and parent directory indicators
            if ($file !== "." && $file !== "..") {
                // Construct the full path for the current file or directory
                $fullPath = "$localPath/$file";

                // Check if the current item is a directory
                if (is_dir($fullPath)) {
                    // Recursively call the function for subdirectories
                    $this->uploadToR2($index, $fullPath);
                } else {
                    // Create a relative path to preserve directory structure in R2
                    // Remove the base path for the index and prepend the index itself
                    $relativePath = $index . '/' . str_replace(storage_path("app/public/hls/$index/"), '', $fullPath);

                    // Upload the file to R2 storage, preserving the directory structure
                    Storage::disk('r2')->put($relativePath, file_get_contents($fullPath));
                }
            }
        }
        return "OK";
    }

    private function getVideoDuration($filePath)
    {
        $ffmpegOutput = shell_exec("ffmpeg -i \"$filePath\" 2>&1");
        preg_match('/Duration: (\d+):(\d+):(\d+\.\d+)/', $ffmpegOutput, $matches);

        if ($matches) {
            $hours   = $matches[1] * 3600;
            $minutes = $matches[2] * 60;
            $seconds = (int) $matches[3];

            return $hours + $minutes + $seconds;
        }

        return 3600;
    }
}

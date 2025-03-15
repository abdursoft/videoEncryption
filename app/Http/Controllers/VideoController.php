<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessVideoReverb;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    // show the video list
    public function index()
    {
        $videos = Video::paginate(10);
        return view('dashboard', compact('videos'));
    }

    // show the upload form
    public function create()
    {
        return view('video.upload');
    }

    // show the video player
    public function player($key){
        $token = bin2hex(random_bytes(16));
        session(['playback_token' => $token]);
        return view('player', compact('key','token'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mkv,avi|max:102400',
            'label' => 'required|array'
        ]);

        // Store the uploaded file in public storage
        $filePath = $request->file('video')->store('videos', 'public');

        // real video path
        $video = storage_path("app/public/{$filePath}");

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
            $keyUri = "$index/key/enc.key";
        } else {
            $keyUri = ("$index/key/enc.key");
        }

        Storage::disk('public')->put("hls/$index/key/enc.keyinfo", "$keyUri\n$keyPath\n$iv");

        Video::create([
            'token'      => $index,
            'video_path' => $filePath,
            'storage' => $request->input('storage')
        ]);

        // Dispatch the processing job with file path instead of UploadedFile object
        ProcessVideoReverb::dispatch($video, $index, $keyInfoPath, $request->input('domain'), $request->input('label'),$request->input('storage'));

        // Start the queue worker automatically
        // Artisan::call('queue:work --timeout=1200');

        return response()->json(['message' => 'Video is processing.']);
    }

    // Get master m3u8 or directories
    public function getMasterPlaylist($videoId)
    {
        $video = Video::find($videoId);
        if($video){
            $disk = Storage::disk($video->storage);
            $masterPlaylistPath = "{$video->token}/master.m3u8";

            if (!$disk->exists($masterPlaylistPath)) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $masterContent = $disk->get($masterPlaylistPath);

            // Replace relative paths with Laravel routes
            $modifiedMaster = preg_replace_callback('/^(lw|md|sd|hd|2k|4k|8k|key)\/index\.m3u8|.*\/enc\.key$/m', function ($matches) use ($videoId) {
                return route('get.hls.file', ['videoId' => $videoId, 'file' => $matches[0]]);
            }, $masterContent);

            return response($modifiedMaster)->header("Content-Type", "application/x-mpegURL");
        }
    }


    // Get hls or key file stream
    public function getHlsFile($videoId, $file, $ext = null)
    {
        $video = Video::find($videoId);
        if($video){
            $disk = Storage::disk($video->storage);
            $filePath = "{$video->token}/{$file}/{$ext}";

            if (!$disk->exists($filePath)) {
                return response()->json(['error' => 'File not found'], 404);
            }
            return $disk->get($filePath);

            return response($disk->get($filePath))
                ->header("Content-Type", $file == 'enc.key' ? "application/octet-stream" : "application/x-mpegURL");
        }
    }

    // Upload hls directory and files in R2 storage
    public function uploadToR2($index, $localPath = null)
    {
        $video = Video::find($index);

        if($video){
            // Set the initial path if not provided
            if ($localPath === null) {
                $localPath = storage_path("app/public/hls/$video->token");
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
                        $relativePath = $video->token . '/' . str_replace(storage_path("app/public/hls/$video->token/"), '', $fullPath);

                        // Upload the file to R2 storage, preserving the directory structure
                        Storage::disk($video->storage)->put($relativePath, file_get_contents($fullPath));
                    }
                }
            }
            return redirect()->route('video.list')->with('success',"Video successfully uploaded in R2 Storage");
        }

    }

    // Get video duration by ffmpeg
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

    // Delete from local
    public function deleteFromLocal($index){
        $path = storage_path("app/public/hls/$index");
        if(is_dir($path)){
            Storage::deleteDirectory("hls/$index");
        }
        Video::where('token',$index)->delete();
        return redirect()->route('video.list')->with('success',"Video successfully remove from Storage");
    }

    // Delete form R2 storage
    public function deleteFromR2($videoId)
    {
        $video = Video::where('token',$videoId)->first();
        if($video){
            $disk = Storage::disk($video->storage);

            // Get all files inside the folder
            $files = $disk->allFiles($video->token);
            $directories = $disk->allDirectories($video->token);

            // Delete all files and folders
            $disk->delete($files);
            foreach ($directories as $dir) {
                $disk->deleteDirectory($dir);
            }

            // Finally, delete the main folder
            $disk->deleteDirectory($video->token);


            // Update the model
            Video::where('token',$video->token)->update([
                'uploaded' => '0',
            ]);
            return redirect()->route('video.list')->with('success',"Video successfully remove from R2 Storage");
        }
    }
}

<?php

namespace App\Jobs;

use App\Events\VideoProcessingProgress;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideoReverb implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $index;
    protected $keyPath;
    protected $domain;
    protected $labels;

    public $timeout = 600; // 10 minutes

    public function __construct($path, $index, $keyPath,$domain,$labels)
    {
        $this->path    = $path;
        $this->index   = $index;
        $this->keyPath = $keyPath;
        $this->domain = $domain;
        $this->labels = $labels;
    }

    public function handle()
    {
        // Define resolutions
        $resolutions = [
            ['name' => 'lw', 'resolution' => '426x240', 'bitrate' => '800k'],       // 240p
            ['name' => 'md', 'resolution' => '640x360', 'bitrate' => '1200k'],      // 360p
            ['name' => 'sd', 'resolution' => '1280x720', 'bitrate' => '2500k'],    // 720p
            ['name' => 'hd', 'resolution' => '1920x1080', 'bitrate' => '5000k'], // 1080p
            ['name' => '2k', 'resolution' => '2560x1440', 'bitrate' => '8000k'],     // 1440p (2K)
            ['name' => '4k', 'resolution' => '3840x2160', 'bitrate' => '16000k'],    // 2160p (4K UHD)
            ['name' => '8k', 'resolution' => '7680x4320', 'bitrate' => '35000k'],    // 4320p (8K UHD)
        ];

        $totalSteps  = count($resolutions) + 1; // +1 for Upload
        $currentStep = 0;

        foreach ($resolutions as $res) {
            if(in_array($res['name'],$this->labels)){
                sleep(1);
                try {
                    Log::info("Processing started for: {$this->path}");
                    $outputDir = storage_path("app/public/hls/{$this->index}/{$res['name']}");
                    if (! file_exists($outputDir)) {
                        mkdir($outputDir, 0755, true);
                    }

                    $variantPlaylist = "{$outputDir}/index.m3u8";
                    $segmentPath     = "{$outputDir}/segment_%03d.ts";

                    $command = "ffmpeg -i \"$this->path\" -vf scale={$res['resolution']} -b:v {$res['bitrate']} -hls_time 10 -hls_key_info_file \"$this->keyPath\" -hls_segment_filename \"$segmentPath\" -hls_playlist_type vod \"$variantPlaylist\"";

                    exec($command, $output, $returnCode);

                    if ($returnCode !== 0) {
                        Log::error("FFmpeg error: " . implode("\n", $output));
                        throw new \Exception("FFmpeg failed to process the video.");
                    }

                    Log::info("Processing completed for: {$this->path}");

                    $currentStep++;
                    $progress = ($currentStep / $totalSteps) * 100;
                    broadcast(new VideoProcessingProgress(round($progress)));
                } catch (\Throwable $th) {
                    Log::error("ProcessVideo Job Failed: " . $th->getMessage());
                    throw $th;
                }
            }
        }

        // Generate master playlist for streaming
        $masterPlaylistPath = storage_path("app/public/hls/$this->index/master.m3u8");
        $masterContent      = "#EXTM3U\n";
        foreach ($resolutions as $res) {
            if(in_array($res['name'],$this->labels)){
                $bandwidth  = preg_replace('/\D/', '', $res['bitrate']) . '000'; // Convert '800k' to '800000'
                $resolution = $res['resolution'];
                $playlist   = "{$res['name']}/index.m3u8";

                $masterContent .= "#EXT-X-STREAM-INF:BANDWIDTH=$bandwidth,RESOLUTION=$resolution\n$playlist\n";
            }
        }
        // Save the master playlist
        file_put_contents($masterPlaylistPath, $masterContent);

        // create poster & thumbnail
        $this->posterThumbnail($this->path,$this->index,$this->domain);

        // Upload to R2
        $this->uploadToR2($this->index);

        // unlink the video upload
        if (file_exists($this->path)) {
            unlink($this->path);
        }

        Video::where('token', $this->index)->update(['uploaded' => '1','hls' => "{$this->domain}/{$this->index}/master.m3u8" ]);
        broadcast(new VideoProcessingProgress(100));
    }

    // poster and thumbnail generate
    private function posterThumbnail($video,$index,$domain){

        $posterDir = storage_path("app/public/hls/$index/thumbnails");
        if(! file_exists($posterDir)){
            mkdir($posterDir,0755,1);
        }

        // Generate Poster (Single Frame at 10s)
        exec("ffmpeg -i {$video} -ss 00:00:10 -vframes 1 -q:v 2 {$posterDir}/poster.jpg");

        // Generate Multiple Thumbnails (One Every 10 Seconds)
        exec("ffmpeg -i {$video} -vf \"fps=1/10,scale=320:-1\" {$posterDir}/thumb_%03d.jpg");
        Video::where('token',$index)->update([
            'poster' => "{$domain}/{$index}/thumbnails/poster.jpg"
        ]);
    }

    // upload in cloud flare r2
    private function uploadToR2($index, $localPath = null)
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
    }
}

<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Pusher\Pusher;

class ProcessVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $path;
    protected $index;
    protected $keyPath;

    public $timeout = 600; // 10 minutes

    public function __construct($path, $index, $keyPath)
    {
        $this->path    = $path;
        $this->index   = $index;
        $this->keyPath = $keyPath;
    }

    public function handle()
    {
        $pusher = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), ['cluster' => env('PUSHER_APP_CLUSTER')]);

        // Define resolutions
        $resolutions = [
            ['name' => 'low', 'resolution' => '426x240', 'bitrate' => '800k'],       // 240p
            ['name' => 'mid', 'resolution' => '640x360', 'bitrate' => '1200k'],      // 360p
            ['name' => 'high', 'resolution' => '1280x720', 'bitrate' => '2500k'],    // 720p
            ['name' => 'fullHd', 'resolution' => '1920x1080', 'bitrate' => '5000k'], // 1080p
            ['name' => '2k', 'resolution' => '2560x1440', 'bitrate' => '8000k'],     // 1440p (2K)
        ];

        $totalSteps  = count($resolutions) + 1; // +1 for Upload
        $currentStep = 0;

        foreach ($resolutions as $res) {
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
                $pusher->trigger('video-channel', 'progress-update', ['progress' => round($progress)]);
            } catch (\Throwable $th) {
                Log::error("ProcessVideo Job Failed: " . $th->getMessage());
                throw $th;
            }
        }

        // Generate master playlist for streaming
        $masterPlaylistPath = storage_path("app/public/hls/$this->index/master.m3u8");
        $masterContent      = "#EXTM3U\n";
        foreach ($resolutions as $res) {
            $bandwidth  = preg_replace('/\D/', '', $res['bitrate']) . '000'; // Convert '800k' to '800000'
            $resolution = $res['resolution'];
            $playlist   = "{$res['name']}/index.m3u8";

            $masterContent .= "#EXT-X-STREAM-INF:BANDWIDTH=$bandwidth,RESOLUTION=$resolution\n$playlist\n";
        }

        // Save the master playlist
        file_put_contents($masterPlaylistPath, $masterContent);

        Storage::disk('public')->delete($this->path);

        // Upload to R2
        $this->uploadToR2($this->index);
        $pusher->trigger('video-channel', 'progress-update', ['progress' => 100]);
    }

    private function uploadToR2($index)
    {
        $localPath = storage_path("app/public/hls/$index");
        $files     = scandir($localPath);

        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                Storage::disk('r2')->put("hls/$index/$file", file_get_contents("$localPath/$file"));
            }
        }
    }
}

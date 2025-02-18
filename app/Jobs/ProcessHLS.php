<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Pusher\Pusher;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;

class ProcessHLS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $inputFile, $index, $resolutions, $keyPath, $keyInfoPath;

    public function __construct($inputFile, $index, $resolutions)
    {
        $this->inputFile = $inputFile;
        $this->index = $index;
        $this->resolutions = $resolutions;
        $this->keyPath = storage_path("app/public/hls/$index/key/enc.key");
        $this->keyInfoPath = storage_path("app/public/hls/$index/key/enc.keyinfo");
    }

    public function handle()
    {
        // Generate AES encryption key
        $key = openssl_random_pseudo_bytes(16);
        Storage::disk('public')->put("hls/{$this->index}/key/enc.key", $key);
        $iv = bin2hex(random_bytes(16)); // IV (Initialization Vector)

        // Generate .keyinfo file
        $keyUrl = env('APP_URL') . "/storage/hls/{$this->index}/key/enc.key";
        Storage::disk('public')->put("hls/{$this->index}/key/enc.keyinfo", "$keyUrl\n{$this->keyPath}\n$iv");

        foreach ($this->resolutions as $res) {
            $outputDir = storage_path("app/public/hls/{$this->index}/{$res['name']}");
            if (!file_exists($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $variantPlaylist = "{$outputDir}/index.m3u8";
            $segmentPath = "{$outputDir}/segment_%03d.ts";

            $command = [
                'ffmpeg', '-i', $this->inputFile,
                '-vf', "scale={$res['resolution']}",
                '-b:v', $res['bitrate'],
                '-hls_time', '10',
                '-hls_key_info_file', $this->keyInfoPath,
                '-hls_segment_filename', $segmentPath,
                '-hls_playlist_type', 'vod',
                $variantPlaylist
            ];

            $process = new Process($command);
            $process->setTimeout(0);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception("FFmpeg error: " . $process->getErrorOutput());
            }
        }

        // Upload all HLS files to Cloudflare R2
        $this->uploadToR2();
    }

    private function uploadToR2()
    {
        $r2Disk = Storage::disk('r2'); // Ensure R2 is set up in Laravel

        // Upload encrypted key
        $r2Disk->put("hls/{$this->index}/key/enc.key", Storage::disk('public')->get("hls/{$this->index}/key/enc.key"));

        // Upload playlists and segments
        foreach ($this->resolutions as $res) {
            $files = Storage::disk('public')->files("hls/{$this->index}/{$res['name']}");

            foreach ($files as $file) {
                $r2Disk->put($file, Storage::disk('public')->get($file));
            }
        }
    }
}

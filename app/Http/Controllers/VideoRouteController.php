<?php
namespace App\Http\Controllers;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class VideoRouteController extends Controller
{
    public function getSignedMasterM3U8(Request $request, $index)
    {
        $video = Video::where('token', $index)->first();
        if ($video) {
            $m3u8Content  = Storage::disk($video->storage)->get("$video->token/master.m3u8");
            $lines        = explode("\n", $m3u8Content);
            $updatedLines = [];

            foreach ($lines as $key=>$line) {
                if (strpos($line, "index.m3u8") !== false) {
                    $signedUrl      = Storage::disk($video->storage)->temporaryUrl("$video->token/{$line}", now()->addMinutes(60));
                    // $updatedLines[] = $signedUrl;
                    session(["key_$key" => $key]);
                    $updatedLines[] = "play/$line?token=$key";
                } else {
                    if (preg_match('/\.ts$/', trim($line))) {
                        $tsSignedUrl    = Storage::disk($video->storage)->temporaryUrl("$video->token/{$resolution}/{$line}", now()->addMinutes(60));
                        $updatedLines[] = $tsSignedUrl;
                    } else {
                        $updatedLines[] = $line;
                    }
                }
            }

            return response(implode("\n", $updatedLines), 200)->header("Content-Type", "application/vnd.apple.mpegurl");
            // return response($m3u8Content, 200)->header("Content-Type", "application/vnd.apple.mpegurl");
        }
    }

    public function getSignedResolutionM3U8(Request $request, $resolution, $file)
    {
        $m3u8Content  = Storage::disk('s3')->get("67d1869947a35/{$resolution}/{$file}");
        $lines        = explode("\n", $m3u8Content);
        $updatedLines = [];

        foreach ($lines as $line) {
            // Handle encrypted key file
            if (strpos($line, "#EXT-X-KEY") !== false) {
                preg_match('/URI="([^"]+)"/', $line, $matches);
                if (! empty($matches[1])) {
                    $keyPath      = trim($matches[1]);
                    $signedKeyUrl = Storage::disk('s3')->temporaryUrl($keyPath, now()->addMinutes(60));
                    $line         = str_replace($matches[1], $signedKeyUrl, $line);
                }
            }

            // Handle .ts segment files
            if (preg_match('/\.ts$/', trim($line))) {
                $tsSignedUrl    = Storage::disk('s3')->temporaryUrl("67d1869947a35/{$resolution}/{$line}", now()->addMinutes(60));
                $updatedLines[] = $tsSignedUrl;
            } else {
                $updatedLines[] = $line;
            }
        }

        return response(implode("\n", $updatedLines), 200)->header("Content-Type", "application/vnd.apple.mpegurl");
    }

    public function getSignedSegment(Request $request, $resolution, $file)
    {
        $signedUrl = Storage::disk('s3')->temporaryUrl("67d1869947a35/{$resolution}/{$file}", now()->addMinutes(60));

        return response()->json(['url' => $signedUrl]);
    }

    public function getSignedKey(Request $request, $key)
    {
        $signedUrl = Storage::disk('s3')->temporaryUrl("67d1869947a35/{$key}", now()->addMinutes(60));

        return response()->json(['url' => $signedUrl]);
    }

    public function getM3U8Signed(Request $request, $file)
    {
        if (session('playback_token') === $request->query('token')) {
            Session::forget('playback_token');
            $video = Video::where('token', $file)->first();
            if ($video) {
                // Get M3U8 file content
                $m3u8Content  = Storage::disk($video->storage)->get("$video->token/md/index.m3u8");
                $lines        = explode("\n", $m3u8Content);
                $updatedLines = [];

                foreach ($lines as $line) {
                    if (strpos($line, ".m3u8") !== false) {
                        // Generate signed URL for resolution M3U8 files
                        $signedUrl      = Storage::disk($video->storage)->temporaryUrl("$video->token/" . trim($line), now()->addMinutes(60));
                        $updatedLines[] = $signedUrl;
                    } elseif (strpos($line, ".ts") !== false) {
                        // Generate signed URL for each .ts segment file
                        $signedUrl      = Storage::disk($video->storage)->temporaryUrl("$video->token/md/" . trim($line), now()->addMinutes(60));
                        $updatedLines[] = $signedUrl;
                    } elseif (strpos($line, 'URI="') !== false) {
                        // Extract encryption key URL and sign it
                        preg_match('/URI="(.*?)"/', $line, $matches);
                        if (! empty($matches[1])) {
                            $keyPath      = $matches[1]; // Get relative path
                            $signedKeyUrl = Storage::disk($video->storage)->temporaryUrl($keyPath, now()->addMinutes(60));
                            $line         = str_replace($matches[1], $signedKeyUrl, $line);
                        }
                        $updatedLines[] = $line;
                    } else {
                        $updatedLines[] = $line;
                    }
                }

                return response(implode("\n", $updatedLines), 200)->header("Content-Type", "application/vnd.apple.mpegurl");
            }
        }
    }

    public function getIndexM3U8Signed(Request $request, $resolution, $file)
    {
        // Fetch the index.m3u8 file for the specified resolution
        $m3u8Content  = Storage::disk('s3')->get("67d1869947a35/{$resolution}/$file");
        $lines        = explode("\n", $m3u8Content);
        $updatedLines = [];

        foreach ($lines as $line) {
            if (strpos($line, ".ts") !== false) {
                // Generate signed URL for each .ts file
                $signedUrl      = Storage::disk('s3')->temporaryUrl("67d1869947a35/{$resolution}/" . trim($line), now()->addMinutes(60));
                $updatedLines[] = $signedUrl;
            } elseif (strpos($line, 'URI="') !== false) {
                // Handle the encryption key if present
                preg_match('/URI="(.*?)"/', $line, $matches);
                if (! empty($matches[1])) {
                    $keyPath      = str_replace("https://sg-ch.e2m2.sg.idrivee2-26.com/", "", $matches[1]); // Get relative path
                    $signedKeyUrl = Storage::disk('s3')->temporaryUrl($keyPath, now()->addMinutes(60));
                    $line         = str_replace($matches[1], $signedKeyUrl, $line);
                }
                $updatedLines[] = $line;
            } else {
                $updatedLines[] = $line; // Keep other lines unchanged
            }
        }

        return response(implode("\n", $updatedLines), 200)->header("Content-Type", "application/vnd.apple.mpegurl");
    }


}

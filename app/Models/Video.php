<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = [
        'token',
        'storage',
        'video_path',
        'segment_path',
        'bucket_path',
        'uploaded',
        'hls',
        'poster'
    ];
}

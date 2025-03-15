<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Video Encoding and upload</title>

    <!-- bootstrap css start  -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap css end  -->

    <!-- custom css start  -->
    <style>
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        body {
            background: linear-gradient(45deg, #ff7e5f, #feb47b, #86a8e7, #7f7fd5);
            background-size: 300% 300%;
            animation: gradient 6s ease infinite;
            margin: 0;
        }
        .border-radius-3{
            border-radius: 23px;
        }
    </style>
    <!-- custom css end  -->
</head>

<body>
    <div class="container mt-5">
        <!-- hero section start  -->
        <div class="row">
            <h3 class="text-center font-bold text-uppercase">Let's Encrypt your videos</h3>
            <div class="d-flex align-items-center justify-content-center">
                <div class="col-12 col-md-7 col-lg-6 text-center">
                    <img src="/images/video-encryptions.png" alt="" class="w-100 h-100 border-radius-3">
                    <a href="/login" class="m-auto text-center"><button class="btn btn-success text-light rounded mt-2">Login To Dashboard</button></a>
                    <h3 class="text-center mt-3 text-light">Convert your videos into HLS</h3>
                </div>
            </div>
        </div>
        <!-- hero section end  -->

        <!-- FFMPEG and video description start  -->
        <div class="row mt-3">
            <div class="col-sm-12 col-md-3 col-lg-2">
                <img src="/images/ffmpeg.png" alt="ffmpeg" class="w-100 h-100 border-radius-3">
            </div>
            <div class="col-sm-12 col-md-9 col-lg-10">
                <p class="mt-3">
                    One video for multiple resolutions. Now you can <strong>convert</strong> your video into HLS with multiple resolutions and you can protect the video with <strong>encryption (AE128) key</strong>. Do the best work with <strong>ffmpeg</strong> and <strong>absVideo Encryption</strong> application.
                </p>

                <p class="mt-3">Supported video formats <strong>MP4, WEBM, MKV, AVI, MPEG-2 MPEG-4, FLV, 3GP, OGG, WMV, and TS</strong> now you can convert those type of videos into an <strong>HLS</strong> video. In the below you can see our video encryption process by visually.</p>

                <h3 class="text-bold text-uppercase mt-5">Power off FFMPEG</h3>
            </div>
        </div>
        <!-- FFMPEG and video description end  -->

        <!-- FFMPEG and video processing start  -->
        <div class="row my-5">
            <img src="/images/encoder.png" alt="video-encoder" class="w-100 h-188 border-rounded-3">
        </div>
        <!-- FFMPEG and video processing end  -->

        <!-- footer section start  -->
        <div class="d-flex align-items-center justify-content-center mt-5 pb-3">
            <div class="text-center">
                <p class="p-0 m-0 d-flex gap-2">Developed by <a href="https://abdursoft.com" target="_blank" class="nav-link text-light">abdursoft</a></p>
                <p class="p-0 m-0">Version 1.0</p>
            </div>
        </div>
        <!-- footer section end  -->
    </div>
</body>

</html>

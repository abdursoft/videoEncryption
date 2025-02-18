<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Video Upload with Progress</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/7.0.3/pusher.min.js"></script>
    @vite(['resources/js/app.js'])
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">

        <video id="videoPlayer" class="video-js vjs-default-skin" controls>
            <source src="http://127.0.0.1:8000/video/67b4950fee847/master.m3u8" type="application/x-mpegURL">
        </video>

        <form id="uploadForm" enctype="multipart/form-data">
            @csrf
            <input type="file" name="video" required>
            <input type="text" value="" name="domain">
            <button type="submit">Upload</button>
        </form>

        <div id="progressContainer" style="width: 100%; background: #eee;margin-top:30px;">
            <div id="progressBar" style="width: 0; height: 20px; background: #76c7c0;"></div>
        </div>
        <div id="progressText">0%</div>

        @include('player')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById("uploadForm").addEventListener("submit", function(event) {
            event.preventDefault();
            var formData = new FormData(this);

            fetch("{{ route('video.upload') }}", {
                    method: "POST",
                    body: formData
                }).then(response => response.json())
                .then(data => {
                    document.getElementById("progressContainer").style.display = "block";
                });
        });

        // Listen for progress updates
        window.addEventListener('load', () => {
            window.Echo.channel('video-processing')
                .listen('.progress.update', (e) => {
                    console.log(`Progress: ${e.progress}%`);
                    // Update your progress bar here
                    document.getElementById('progressBar').style.width = `${e.progress}%`;
                    document.getElementById('progressText').innerText = `${e.progress}%`;
                });
        })
    </script>
</body>

</html>

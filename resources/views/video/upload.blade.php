<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Upload Video ') }}
        </h2>
    </x-slot>

    <!-- Video upload form -->
    <div class="py-12">
        <div class="max:w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col items-center justify-center">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg  w-full md:w-2/5">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" id="uploadForm" action="{{ route('video.upload') }}" enctype="multipart/form-data">
                        @csrf

                        <!-- Domain Name -->
                        <div>
                            <x-input-label for="name" :value="__('Domain Name')" />
                            <x-text-input id="name" class="block mt-1 w-full" type="url" name="domain"
                                :value="old('domain')" required autofocus autocomplete="domain"
                                placeholder="R2 public domain name" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- File selection -->
                        <div class="mt-4">
                            <x-input-label for="video_file" :value="__('Confirm Password')" />
                            <x-text-input id="video_file" class="block mt-1 w-full" type="file" name="video"
                                required />

                            <x-input-error :messages="$errors->get('video_file')" class="mt-2" />
                        </div>

                        <!-- Storage selection -->
                        <select name="storage" id="storage" class="w-full my-2 rounded-md !bg-black-500 selected:text-black-500">
                            <option value="">No</option>
                            <option value="s3">AWS S3</option>
                            <option value="r2">Cloudflare R2</option>
                        </select>

                        {{-- video encrypt label  --}}
                        <div class="mt-4">
                            <p class="text-white mb-2">Select video labels</p>
                            <div class="text-white flex items-center flex-wrap">
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="lw" value="lw"><label for="lw">Low</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="md" value="md"><label for="md">Medium</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="sd" value="sd"><label for="sd">High</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="hd" value="hd"><label for="hd">Full HD</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="2k" value="2k"><label for="2k">2K UHD</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="4k" value="4k"><label for="4k">4K UHD</label></div>
                                <div class="flex items-center gap-2 w-1/4 my-1"><input type="checkbox" name="label[]"
                                        id="8k" value="8k"><label for="8k">8K UHD</label></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ms-4">
                                {{ __('Upload') }}
                            </x-primary-button>
                        </div>

                        {{-- video upload progress  --}}
                        <div id="progressContainer" style="width: 100%; background: #eee;margin-top:30px;display:none;">
                            <div id="progressBar" style="width: 0; height: 20px; background: #76c7c0;"></div>
                        </div>
                        <div id="progressText" class="hidden text-white">0%</div>
                    </form>

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
                                    document.getElementById("progressText").style.display = "block";
                                });
                        });

                        // Listen for progress updates
                        window.addEventListener('load', () => {
                            window.Echo.channel('video-processing')
                                .listen('.progress.update', (e) => {
                                    // Update your progress bar here
                                    document.getElementById('progressBar').style.width = `${e.progress}%`;
                                    document.getElementById('progressText').innerText = `${e.progress}%`;
                                });
                        })
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<x-public-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="container vh-100">
                    <div class="d-flex align-items-center justify-content-center w-100 vh-100">
                        <div id="player"><video src="" id="video" width="400" height="350" controls ></video></div>
                    </div>
                </div>
                <script>
                    async function getSignedMaster() {
                        const response = await fetch(`/hls/index/md`);
                        if (!response.ok) {
                            console.error("Failed to fetch signed master.m3u8");
                            return null;
                        }
                        return URL.createObjectURL(await response.blob()); // Convert response to blob URL
                    }
                </script>

                <script type="module">
                    import Player from 'https://cdn.abdursoft.com/video/beta.js'
                    let response = await getSignedMaster();
                    const video = Player({
                        id: 'player',
                        src: `/master/{{$key}}?type=.m3u8&token={{$token}}`,
                        poster: 'https://i.ytimg.com/vi/ECiqd98bGLo/hqdefault.jpg?sqp=-oaymwEbCKgBEF5IVfKriqkDDggBFQAAiEIYAXABwAEG&rs=AOn4CLDH2-SFnWbuoOlWXqT5jzSl9UtK3w',
                        encrypt: false,
                        videoDecryption: false,
                        api_key: 'c28zb2llcXVkZWFyb3I3N2xkZWlnNzBmdWFsMDZodDZj',
                        background: 'darkblue',
                        playback: {
                            speed: [0.5, 1, 1.5, 2],
                            default: 1,
                            placement: {
                                content: "x",
                            }
                        },
                        backward: true,
                        forward: true,
                        share: true,
                        pip: true,
                        subtitle: [
                            'https://cdn.bitmovin.com/content/assets/art-of-motion-dash-hls-progressive/thumbnails/f08e80da-bf1d-4e3d-8899-f0f6155f6efa.vtt'
                        ],
                        analytics: {
                            tag: 'G-FKJ9WK8CE5',
                            appName: 'Live-radio'
                        },
                        logo: {
                            url: "https://abdursoft.com/assets/images/logo/abdursoft-f.svg",
                            position: {
                                position: "absolute",
                                width: '70px',
                                height: '65px',
                                top: "10px",
                                right: "30px",
                                zIndex: 4,
                                borderRadius: "50%",
                                overflow: "hidden"
                            }
                        },
                        snap: 'no',
                        vast: false,
                        snapIcon: false,
                        iconHoverColor: "rgba(36, 107, 173, 0.88)",
                        progress: {
                            css: {
                                width: "98%",
                                height: "5px",
                                position: "absolute",
                                bottom: "50px",
                                background: "#f9f9f9",
                                left: '1%',
                                right: '1%',
                                zIndex: 5,
                                borderRadius: '4px',
                                cursor: "pointer",
                                overflow: 'hidden',
                                transition: "all 0.3s"
                            },
                            defaultHeight: "5px",
                            extendHeight: "8px"
                        },
                        progressTimeline: {
                            position: 'absolute',
                            width: 0,
                            height: '100%',
                            top: 0,
                            left: 0,
                            background: 'indigo',
                            cursor: 'pointer'
                        },
                        volumeContainer: {
                            css: {
                                width: '110px',
                                display: 'flex',
                                alignItems: 'center',
                                position: 'absolute',
                                justifyContent: "center",
                                bottom: '118px',
                                right: '50px',
                                padding: '8px 10px',
                                zIndex: 5,
                                background: "rgba(0,0,0,0.5)",
                                transform: "rotate(-90deg)",
                                borderRadius: "5px"
                            },
                            type: 'vertical'
                        },
                        volumeSliderArea: {
                            width: '100%',
                            height: '15px',
                            background: 'gray',
                            cursor: 'pointer',
                            transition: '0.5s',
                            position: 'relative',
                        },
                        volumeSlider: {
                            width: '0px',
                            height: '15px',
                            background: 'indigo',
                            cursor: 'pointer',
                            transition: '0.5s',
                            position: 'absolute'
                        },
                        durationArea: {
                            css: {
                                display: 'flex',
                                zIndex: 2,
                                alignItems: 'center',
                                gap: "5px",
                            },
                            divider: {
                                content: "/",
                                css: {
                                    fontSize: "13px",
                                    color: "#fff",
                                    fontWeight: "bold",
                                    marginBottom: "0px",
                                    paddingBottom: "0px"
                                }
                            },
                        },
                        controls: {
                            left: ['playPauseControl', 'backwardControl', 'speedPlacement', 'forwardControl', 'durationArea'],
                            right: ['castControl', 'shareControl', 'playlistControl', 'volumeControl', 'settingsControl',
                                'screenControl'
                            ],
                            background: "rgba(0,0,0,0.3)"
                        },
                        contextMenu: true,
                        lang: "EN",
                        tooltip: true
                    });
                </script>

                {{-- <script>
                    async function getSignedMaster() {
                        const response = await fetch(`/video/signed-master/master.m3u8`);
                        if (!response.ok) {
                            console.error("Failed to fetch signed master.m3u8");
                            return null;
                        }
                        console.log(response);
                        return URL.createObjectURL(await response.blob()); // Convert response to blob URL
                    }

                    async function playVideo() {
                        const signedM3U8Url = await getSignedMaster();
                        if (!signedM3U8Url) return;

                        var video = document.getElementById('video');
                        video.src = signedM3U8Url; // Load signed master.m3u8 URL
                    }

                    playVideo();
                </script> --}}
            </div>
        </div>
    </div>
</x-public-layout>

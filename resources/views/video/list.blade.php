    <div class="py-12">
        <div class="max:w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col items-center justify-center">
            <div
                class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg  w-full md:w-4/5 lg:w-4/6 p-4 overflow-x-auto ">
                <!-- video list table -->
                <table class="w-full p-2 text-gray-900 dark:text-gray-100 table-auto border-collapse">
                    <thead>
                        <tr class="text-left">
                            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Token</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Poster</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Uploaded</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Created at</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (!empty($videos))
                            @foreach ($videos as $video)
                                <tr>
                                    <td class="px-6 py-4">#{{ $video->token }}</td>
                                    <td class="px-6 py-4"><img src="{{ $video->poster }}" alt=""
                                            class="w-[60px] h-[40px]"></td>
                                    <td class="px-6 py-4">@php echo $video->uploaded == '1' ? 'Yes' : 'No' @endphp</td>
                                    <td class="px-6 py-4">{{ date('d F Y', strtotime($video->created_at)) }}</td>
                                    <td class="flex items-start gap-4 px-6 py-4">
                                        @if ($video->uploaded != '1')
                                            <x-nav-link :href="route('r2.upload', $video->token)" class="hover:text-red-400">
                                                <div class="relative">
                                                    <button data-tooltip-button="tooltip-up-{{ $video->id }}"
                                                        class="px-4 py-2 bg-blue-600 text-white rounded">
                                                        <i class="iconoir-cloud-upload text-xl hover:text-red-500"></i>
                                                    </button>
                                                    <div id="tooltip-up-{{ $video->id }}"
                                                        class="hidden px-3 py-1 text-sm text-white bg-gray-800 rounded-md shadow-md">
                                                        Upload R2
                                                    </div>
                                                </div>
                                            </x-nav-link>
                                        @else
                                            <x-nav-link :href="route('r2.remove', $video->token)" class="hover:text-red-400">
                                                <div class="relative">
                                                    <button data-tooltip-button="tooltip-rm-{{ $video->id }}"
                                                        class="px-4 py-2 bg-red-400 text-white rounded">
                                                        <i class="iconoir-bin-minus-in text-xl hover:text-red-500"></i>
                                                    </button>
                                                    <div id="tooltip-rm-{{ $video->id }}"
                                                        class="hidden px-3 py-1 text-sm text-white bg-gray-800 rounded-md shadow-md">
                                                        Remove R2
                                                    </div>
                                                </div>
                                            </x-nav-link>
                                        @endif
                                        <x-nav-link :href="route('file.remove', $video->token)" class="hover:text-red-400">
                                            <div class="relative">
                                                <button data-tooltip-button="tooltip-dl-{{ $video->id }}"
                                                    class="px-4 py-2 bg-yellow-800 text-white rounded">
                                                    <i class="iconoir-trash text-xl hover:text-red-500"></i>
                                                </button>
                                                <div id="tooltip-dl-{{ $video->id }}"
                                                    class="hidden px-3 py-1 text-sm text-white bg-gray-800 rounded-md shadow-md">
                                                    Delete
                                                </div>
                                            </div>
                                        </x-nav-link>
                                        <x-nav-link :href="route('player', $video->token)" target="_blank"
                                            class="hover:text-red-400 text-decoration-none">
                                            <div class="relative">
                                                <button data-tooltip-button="tooltip-op-{{ $video->id }}"
                                                    class="px-4 py-2 bg-green-600 text-white rounded">
                                                    <i class="iconoir-modern-tv text-xl hover:text-red-500"></i>
                                                </button>
                                                <div id="tooltip-op-{{ $video->id }}"
                                                    class="hidden px-3 py-1 text-sm text-white bg-gray-800 rounded-md shadow-md">
                                                    Open
                                                </div>
                                            </div>
                                        </x-nav-link>

                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <!-- video list pagination -->
                <div class="mt-4">
                    {{ $videos->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const tooltipButtons = document.querySelectorAll("[data-tooltip-button]");

            tooltipButtons.forEach(button => {
                const tooltipId = button.getAttribute("data-tooltip-button");
                const tooltip = document.querySelector(`#${tooltipId}`);

                const popperInstance = Popper.createPopper(button, tooltip, {
                    placement: 'top', // Positions: top, bottom, left, right
                    modifiers: [{
                            name: 'preventOverflow',
                            options: {
                                boundary: document.body
                            },
                        },
                        {
                            name: 'offset',
                            options: {
                                offset: [0, 10]
                            }, // Adjusts spacing
                        }
                    ],
                });

                button.addEventListener("mouseenter", () => {
                    tooltip.classList.remove("hidden", "opacity-0");
                    tooltip.classList.add("opacity-100", "visible");
                    popperInstance.update();
                });

                button.addEventListener("mouseleave", () => {
                    tooltip.classList.add("hidden", "opacity-0");
                    tooltip.classList.remove("opacity-100", "visible");
                });
            });
        });
    </script>

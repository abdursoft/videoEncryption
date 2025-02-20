<div class="relative">
    <button data-tooltip-button="tooltip-{{$video->id}}" class="px-4 py-2 bg-blue-600 text-white rounded">
        Button {{$video->id}}
    </button>
    <div id="tooltip-{{$video->id}}" class="hidden px-3 py-1 text-sm text-white bg-gray-800 rounded-md shadow-md">
        Tooltip for Button {{$video->id}}
    </div>
</div>

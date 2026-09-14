@props(['href', 'filename' => null])

<a href="{{ $href }}" data-no-loading
   x-data="pdfExport()"
   @click.prevent="download($el.href, @js($filename))"
   :class="{ 'opacity-70 cursor-wait pointer-events-none': loading }"
   {{ $attributes }}>
    <span x-show="!loading" x-cloak class="contents">{{ $slot }}</span>
    <span x-show="loading" x-cloak class="flex items-center gap-2">
        <svg class="animate-spin h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        Generating PDF…
    </span>
</a>

<div id="page-loader" class="fixed inset-0 z-[100] bg-white/70 backdrop-blur-xs flex items-center justify-center pointer-events-none opacity-0 transition-opacity duration-150">
    <div class="flex flex-col items-center gap-3">
        <svg class="animate-spin h-9 w-9 text-eids-primary" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Loading…</span>
    </div>
</div>

<script>
(function () {
    var loader = document.getElementById('page-loader');
    var visible = false;

    function show() {
        if (visible) return;
        visible = true;
        loader.classList.remove('opacity-0', 'pointer-events-none');
        loader.classList.add('opacity-100');
    }

    function hide() {
        visible = false;
        loader.classList.add('opacity-0', 'pointer-events-none');
        loader.classList.remove('opacity-100');
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        if (link.hasAttribute('data-no-loading')) return;
        if (link.target === '_blank' || link.hasAttribute('download')) return;
        if (link.getAttribute('href').startsWith('#')) return;
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;

        show();
    });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute('data-no-loading')) return;
        show();
    });

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) hide();
    });
})();
</script>

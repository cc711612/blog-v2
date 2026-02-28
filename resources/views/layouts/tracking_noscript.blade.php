@if(config('services.google.tag'))
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id={{ config('services.google.tag') }}"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
@endif

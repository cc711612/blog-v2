@if(config('services.google.analytics_id'))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', @json(config('services.google.analytics_id')));
</script>
@endif

@if(config('services.google.tag'))
<script>
    (function(w, d, l, i) {
        w[l] = w[l] || [];
        w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});

        function loadGtm() {
            var firstScript = d.getElementsByTagName('script')[0];
            var gtmScript = d.createElement('script');
            var dataLayer = l !== 'dataLayer' ? '&l=' + l : '';
            gtmScript.async = true;
            gtmScript.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dataLayer;
            firstScript.parentNode.insertBefore(gtmScript, firstScript);
        }

        if ('requestIdleCallback' in w) {
            w.requestIdleCallback(loadGtm, { timeout: 2000 });
        } else {
            w.addEventListener('load', loadGtm, { once: true });
        }
    })(window, document, 'dataLayer', @json(config('services.google.tag')));
</script>
@endif

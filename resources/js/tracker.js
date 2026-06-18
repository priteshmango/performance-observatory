(function() {
    window.addEventListener('load', function() {
        // Wait a bit to ensure all metrics are gathered
        setTimeout(gatherAndSendMetrics, 1000);
    });

    function gatherAndSendMetrics() {
        if (!window.performance || !window.performance.timing) return;
        
        const timing = window.performance.timing;
        const metrics = {
            ttfb: timing.responseStart - timing.navigationStart,
            dom_interactive: timing.domInteractive - timing.navigationStart,
            dom_complete: timing.domComplete - timing.navigationStart,
            load_event: timing.loadEventEnd - timing.navigationStart,
            dns: timing.domainLookupEnd - timing.domainLookupStart,
            tcp: timing.connectEnd - timing.connectStart,
            request: timing.responseStart - timing.requestStart,
            response: timing.responseEnd - timing.responseStart,
        };

        // Gather Web Vitals if supported
        try {
            if (window.PerformanceObserver) {
                const paintObserver = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        metrics[entry.name] = entry.startTime;
                    }
                });
                paintObserver.observe({ type: 'paint', buffered: true });
            }
        } catch (e) {}

        const requestId = document.querySelector('meta[name="observatory-id"]')?.getAttribute('content');
        const endpoint = document.querySelector('meta[name="observatory-endpoint"]')?.getAttribute('content');

        if (requestId && endpoint) {
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    metrics: metrics
                })
            }).catch(console.error);
        }
    }
})();

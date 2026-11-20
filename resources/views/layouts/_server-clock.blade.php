{{--
    The server's clock on this page, so the vidiwall, the phones and the console count a show against the same moments.
    Scripts call serverClock(), pass each answer's server_now_ms with read(sentAt, receivedAt, serverNow), and turn a
    server time into this page's performance.now() time with local(serverMs).
--}}
<script>
    /* The server read its time somewhere between the request going out and
       the answer coming back, so each answer bounds the difference between
       the two clocks. The recent answers narrow it down together; when they
       disagree, the clock starts over from the newest one. */
    window.serverClock = window.serverClock || function () {
        let readings = [];

        function bounds() {
            let low = -Infinity, high = Infinity;
            readings.forEach(reading => {
                low = Math.max(low, reading.low);
                high = Math.min(high, reading.high);
            });
            return [low, high];
        }

        return {
            read(sentAt, receivedAt, serverNow) {
                if (typeof serverNow !== 'number') return;
                readings.push({ low: serverNow - receivedAt, high: serverNow - sentAt });
                if (readings.length > 20) readings.shift();
                const [low, high] = bounds();
                if (low > high) readings = readings.slice(-1);
            },
            local(serverMs) {
                if (typeof serverMs !== 'number' || !readings.length) return null;
                const [low, high] = bounds();
                return serverMs - (low + high) / 2;
            },
        };
    };
</script>

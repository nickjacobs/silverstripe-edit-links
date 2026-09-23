/**
 * Attaches edit badges to the page and to Elemental blocks using the JSON
 * manifest the middleware writes before </body>. Blocks are located by the
 * anchor id Elemental renders on each holder; anything not found is skipped.
 */
(function () {
    'use strict';

    function attach() {
        var node = document.getElementById('ss-edit-links-data');
        if (!node) {
            return;
        }

        var data;
        try {
            data = JSON.parse(node.textContent || '{}');
        } catch (e) {
            return;
        }

        if (data.page) {
            document.body.insertAdjacentHTML('beforeend', data.page);
        }

        (data.elements || []).forEach(function (item) {
            var host = item.anchor ? document.getElementById(item.anchor) : null;
            if (!host || host.querySelector(':scope > .ss-edit-link--element')) {
                return;
            }
            if (window.getComputedStyle(host).position === 'static') {
                host.classList.add('ss-edit-link-host');
            }
            host.insertAdjacentHTML('beforeend', item.html);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attach);
    } else {
        attach();
    }
})();

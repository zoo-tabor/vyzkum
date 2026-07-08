/**
 * Zdravotni historie v dotazniku: strom zaskrtavatek nemoci.
 * - zaskrtnuti kategorie (.dh-cat-toggle) rozbali/schova seznam nemoci (.dh-children),
 * - zaskrtnuti nemoci (.dh-leaf-check) odkryje pole obdobi (.dh-dates),
 * - "stale probiha" (.dh-ongoing) zablokuje/vymaze koncove datum (.dh-to).
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        document.querySelectorAll('[data-dh]').forEach(function (root) {
            root.addEventListener('change', function (e) {
                var t = e.target;
                if (!t || !t.classList) { return; }

                if (t.classList.contains('dh-cat-toggle')) {
                    var label = t.closest('.dh-cat-label');
                    var box = label ? label.nextElementSibling : null;
                    if (box && box.classList.contains('dh-children')) { box.hidden = !t.checked; }
                } else if (t.classList.contains('dh-leaf-check')) {
                    var lbl = t.closest('label');
                    var dates = lbl ? lbl.nextElementSibling : null;
                    if (dates && dates.classList.contains('dh-dates')) { dates.hidden = !t.checked; }
                } else if (t.classList.contains('dh-ongoing')) {
                    var wrap = t.closest('.dh-dates');
                    var to = wrap ? wrap.querySelector('.dh-to') : null;
                    if (to) { to.disabled = t.checked; if (t.checked) { to.value = ''; } }
                }
            });
        });
    });
})();

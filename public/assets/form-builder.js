/**
 * Builder dotazniku: ukazuje jen pole relevantni pro zvoleny typ otazky.
 * - [data-qtypes="a,b"]  = zobrazit jen pro tyto typy (jinak skryt),
 * - [data-qhide="a,b"]   = skryt pro tyto typy (jinak zobrazit).
 * Aplikuje se na kazdy formular, ktery ma <select name="type"> (pridani i editace).
 */
(function () {
    'use strict';

    function apply(form) {
        var sel = form.querySelector('select[name="type"]');
        if (!sel) { return; }
        var type = sel.value;
        form.querySelectorAll('[data-qtypes]').forEach(function (el) {
            el.hidden = el.getAttribute('data-qtypes').split(',').indexOf(type) === -1;
        });
        form.querySelectorAll('[data-qhide]').forEach(function (el) {
            el.hidden = el.getAttribute('data-qhide').split(',').indexOf(type) !== -1;
        });
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        document.querySelectorAll('form').forEach(function (form) {
            var sel = form.querySelector('select[name="type"]');
            if (!sel) { return; }
            apply(form);
            sel.addEventListener('change', function () { apply(form); });
        });
    });
})();

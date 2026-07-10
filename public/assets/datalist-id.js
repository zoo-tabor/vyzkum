/**
 * Naseptavac pres nativni <datalist>: uzivatel pise do textoveho pole, vybere z
 * navrhu, a do skryteho pole se ulozi ID/kod vybrane polozky.
 * Pouziti:
 *   <input type="text" list="owners-list" data-idsync="owner_id" data-idattr="id">
 *   <input type="hidden" name="owner_id" id="owner_id">
 *   <datalist id="owners-list"><option value="Jmeno" data-id="5"></option></datalist>
 * data-idsync = id skryteho pole, data-idattr = ktery data-* atribut option nese ID.
 */
(function () {
    'use strict';

    function sync(input) {
        var listId = input.getAttribute('list');
        var dl = listId ? document.getElementById(listId) : null;
        var hidden = document.getElementById(input.getAttribute('data-idsync'));
        if (!dl || !hidden) { return; }
        var attr = 'data-' + (input.getAttribute('data-idattr') || 'id');
        var val = input.value;
        var found = '';
        var opts = dl.options;
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].value === val) { found = opts[i].getAttribute(attr) || ''; break; }
        }
        hidden.value = found;
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-idsync]').forEach(function (input) {
            input.addEventListener('input', function () { sync(input); });
            input.addEventListener('change', function () { sync(input); });
            sync(input); // init (prevyplneni)
        });
    });
})();

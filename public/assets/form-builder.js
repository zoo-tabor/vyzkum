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

    // visible_if picker: hodnotu podminky nabidni podle typu ovladajici otazky
    // (yes_no -> yes/no, single/multiple_choice -> klice moznosti), jinak volny text.
    // Odpada tim moznost zadat label misto hodnoty (podminka pak nikdy nesedela).
    function condControls(form) {
        var holder = form.querySelector('[data-cond-map]');
        var ctrl = form.querySelector('select[name="visible_if_question"]');
        var sel = form.querySelector('[data-cond-value]');
        var txt = form.querySelector('[data-cond-text]');
        if (!holder || !ctrl || !sel || !txt) { return null; }
        var map;
        try { map = JSON.parse(holder.getAttribute('data-cond-map') || '{}'); } catch (e) { map = {}; }
        return { ctrl: ctrl, sel: sel, txt: txt, map: map };
    }

    function applyCond(c) {
        var qkey = c.ctrl.value;
        var values = qkey ? c.map[qkey] : null;
        if (values && values.length) {
            var keep = c.sel.value || (c.sel.getAttribute('data-cond-current') || '');
            c.sel.innerHTML = '';
            var blank = document.createElement('option');
            blank.value = '';
            blank.textContent = '– vyberte –';
            c.sel.appendChild(blank);
            values.forEach(function (v) {
                var o = document.createElement('option');
                o.value = v[0];
                o.textContent = v[1] + ' (' + v[0] + ')';
                if (v[0] === keep) { o.selected = true; }
                c.sel.appendChild(o);
            });
            c.sel.hidden = false; c.sel.disabled = false;
            c.txt.hidden = true; c.txt.disabled = true;
        } else if (qkey) {
            c.txt.hidden = false; c.txt.disabled = false;
            c.sel.hidden = true; c.sel.disabled = true;
        } else {
            c.txt.hidden = true; c.txt.disabled = true;
            c.sel.hidden = true; c.sel.disabled = true;
        }
    }

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        document.querySelectorAll('form').forEach(function (form) {
            var sel = form.querySelector('select[name="type"]');
            if (sel) {
                apply(form);
                sel.addEventListener('change', function () { apply(form); });
            }
            var c = condControls(form);
            if (c) {
                applyCond(c);
                c.ctrl.addEventListener('change', function () { applyCond(c); });
            }
        });
    });
})();

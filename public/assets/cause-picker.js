/**
 * Kaskadovy vyber priciny umrti z hierarchickeho stromu.
 * Ocekava <script type="application/json" id="cause-tree">[...]</script> a jeden
 * nebo vice kontejneru [data-cause-picker] s .cause-levels, skrytym inputem
 * name=death_cause_id a .cause-note (textarea name=death_cause_note).
 * Volitelne data-selected="<id listu>" pro predvyplneni cele cesty (editace).
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    ready(function () {
        var treeEl = document.getElementById('cause-tree');
        if (!treeEl) { return; }
        var tree;
        try { tree = JSON.parse(treeEl.textContent); } catch (e) { return; }
        if (!tree) { return; }

        function buildLevel(levels, nodes, picker) {
            var sel = document.createElement('select');
            sel.className = 'cause-level';
            var def = document.createElement('option');
            def.value = ''; def.textContent = picker.getAttribute('data-placeholder') || '– vyberte –';
            sel.appendChild(def);
            nodes.forEach(function (n, i) {
                var o = document.createElement('option');
                o.value = String(i); o.textContent = n.label;
                sel.appendChild(o);
            });
            sel.addEventListener('change', function () {
                while (sel.nextSibling) { levels.removeChild(sel.nextSibling); }
                var hidden = picker.querySelector('input[name=death_cause_id]');
                var noteBox = picker.querySelector('.cause-note');
                hidden.value = '';
                if (noteBox) { noteBox.hidden = true; }
                if (sel.value === '') { return; }
                var node = nodes[parseInt(sel.value, 10)];
                if (node.children && node.children.length) {
                    buildLevel(levels, node.children, picker);
                } else {
                    hidden.value = node.id;
                    if (noteBox) { noteBox.hidden = !node.has_note; }
                }
            });
            levels.appendChild(sel);
        }

        function findPath(nodes, targetId) {
            for (var i = 0; i < nodes.length; i++) {
                var n = nodes[i];
                if (n.id === targetId) { return [i]; }
                if (n.children && n.children.length) {
                    var sub = findPath(n.children, targetId);
                    if (sub) { return [i].concat(sub); }
                }
            }
            return null;
        }

        document.querySelectorAll('[data-cause-picker]').forEach(function (picker) {
            var levels = picker.querySelector('.cause-levels');
            levels.innerHTML = '';
            buildLevel(levels, tree, picker);

            var selId = parseInt(picker.getAttribute('data-selected') || '0', 10);
            if (selId > 0) {
                var path = findPath(tree, selId);
                if (path) {
                    path.forEach(function (index, depth) {
                        var sel = levels.querySelectorAll('select')[depth];
                        if (sel) { sel.value = String(index); sel.dispatchEvent(new Event('change')); }
                    });
                }
            }
        });
    });
})();

/**
 * Znovupouzitelna klientska datatabulka (bez zavislosti).
 * Aktivace: <table data-datatable> ... </table> (cely dataset je v DOM).
 * Umi: razeni A->Z/Z->A nad celym datasetem na vsech sloupcich, sloupcovy filtr
 * "hledacek" (obsahuje + vyber hodnot), globalni hledani, volbu poctu radku na
 * stranku a cislovane strankovani dole vlevo.
 *
 * Nastaveni na <th>: data-nosort (bez razeni), data-nofilter (bez sloupc. filtru),
 *   data-type="num" (ciselne razeni; bunky mohou mit data-sort s ciselnou hodnotou).
 * Nastaveni na <table>: data-per-page="25" (vychozi), data-per-page-options="25,50,100,all".
 */
(function () {
    'use strict';

    function text(el) { return (el ? el.textContent : '').trim(); }

    function cellSortValue(row, idx) {
        var td = row.cells[idx];
        if (!td) { return ''; }
        var ds = td.getAttribute('data-sort');
        return ds !== null ? ds : text(td);
    }

    function init(table) {
        if (!table.tHead || !table.tBodies.length) { return; }
        var headRow = table.tHead.rows[0];
        var headers = Array.prototype.slice.call(headRow.cells);
        var tbody = table.tBodies[0];
        var allRows = Array.prototype.slice.call(tbody.rows);

        var perPageDefault = parseInt(table.getAttribute('data-per-page'), 10) || 25;
        var perPageOptions = (table.getAttribute('data-per-page-options') || '25,50,100,all')
            .split(',').map(function (s) { return s.trim(); });

        var state = {
            sortIdx: -1,
            sortAsc: true,
            search: '',
            colFilters: {},           // idx -> { set: Set|null, text: string }
            perPage: perPageDefault,   // number nebo Infinity
            page: 1
        };

        // --- Toolbar (nad tabulkou): pocet radku + globalni hledani ---
        var toolbar = document.createElement('div');
        toolbar.className = 'dt-toolbar';

        var left = document.createElement('div');
        left.className = 'dt-toolbar__left';
        var ppLabel = document.createElement('label');
        ppLabel.className = 'dt-perpage';
        ppLabel.appendChild(document.createTextNode('Zobrazit '));
        var ppSelect = document.createElement('select');
        perPageOptions.forEach(function (opt) {
            var o = document.createElement('option');
            var isAll = opt === 'all' || opt === 'vse';
            o.value = isAll ? 'all' : opt;
            o.textContent = isAll ? 'vše' : opt;
            if ((isAll && state.perPage === Infinity) || parseInt(opt, 10) === state.perPage) { o.selected = true; }
            ppSelect.appendChild(o);
        });
        ppLabel.appendChild(ppSelect);
        ppLabel.appendChild(document.createTextNode(' záznamů'));
        left.appendChild(ppLabel);

        var right = document.createElement('div');
        right.className = 'dt-toolbar__right';
        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'dt-search';
        search.placeholder = 'Hledat v tabulce…';
        right.appendChild(search);

        toolbar.appendChild(left);
        toolbar.appendChild(right);
        table.parentNode.insertBefore(toolbar, table);

        // --- Footer (pod tabulkou): cislovane strankovani + info ---
        var footer = document.createElement('div');
        footer.className = 'dt-footer';
        var pager = document.createElement('div');
        pager.className = 'dt-pager';
        var info = document.createElement('div');
        info.className = 'dt-info muted';
        footer.appendChild(pager);
        footer.appendChild(info);
        table.parentNode.insertBefore(footer, table.nextSibling);

        // --- Hlavicky: nazev + radek s ovladanim (razeni sipky + filtr) ---
        headers.forEach(function (th, idx) {
            var canSort = !th.hasAttribute('data-nosort');
            var canFilter = !th.hasAttribute('data-nofilter');
            th.classList.add('dt-th');

            // Puvodni obsah th (nazev sloupce) zabalime do popisku.
            var label = document.createElement('div');
            label.className = 'dt-th-label';
            while (th.firstChild) { label.appendChild(th.firstChild); }
            th.appendChild(label);

            if (!canSort && !canFilter) { return; }

            var controls = document.createElement('div');
            controls.className = 'dt-th-controls';

            if (canSort) {
                var sortWrap = document.createElement('span');
                sortWrap.className = 'dt-sort';
                var asc = document.createElement('button');
                asc.type = 'button';
                asc.className = 'dt-sort-btn dt-sort-asc';
                asc.title = 'Seřadit vzestupně (A→Z)';
                asc.innerHTML = '&#9650;'; // trojuhelnik nahoru
                var desc = document.createElement('button');
                desc.type = 'button';
                desc.className = 'dt-sort-btn dt-sort-desc';
                desc.title = 'Seřadit sestupně (Z→A)';
                desc.innerHTML = '&#9660;'; // trojuhelnik dolu
                asc.addEventListener('click', function () { setSort(idx, true); });
                desc.addEventListener('click', function () { setSort(idx, false); });
                sortWrap.appendChild(asc);
                sortWrap.appendChild(desc);
                controls.appendChild(sortWrap);
            }
            if (canFilter) {
                controls.appendChild(buildFilter(idx));
            }
            th.appendChild(controls);
        });

        function setSort(idx, asc) {
            if (state.sortIdx === idx && state.sortAsc === asc) {
                state.sortIdx = -1; // opakovany klik na aktivni smer = zrusit razeni
            } else {
                state.sortIdx = idx;
                state.sortAsc = asc;
            }
            state.page = 1;
            render();
        }

        function buildFilter(idx) {
            var wrap = document.createElement('span');
            wrap.className = 'dt-filter';
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'dt-filter-btn';
            btn.title = 'Filtrovat sloupec';
            btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 16 16" aria-hidden="true">'
                + '<path fill="currentColor" d="M1 2h14l-5.5 6.5V14l-3 1.5V8.5z"/></svg>';
            wrap.appendChild(btn);

            var pop = document.createElement('div');
            pop.className = 'dt-popover';
            pop.hidden = true;

            var pSearch = document.createElement('input');
            pSearch.type = 'search';
            pSearch.className = 'dt-popover__search';
            pSearch.placeholder = 'obsahuje…';

            var actionsTop = document.createElement('div');
            actionsTop.className = 'dt-popover__actions';
            var allBtn = document.createElement('button');
            allBtn.type = 'button'; allBtn.className = 'dt-link'; allBtn.textContent = 'Vybrat vše';
            var noneBtn = document.createElement('button');
            noneBtn.type = 'button'; noneBtn.className = 'dt-link'; noneBtn.textContent = 'Zrušit výběr';
            actionsTop.appendChild(allBtn);
            actionsTop.appendChild(noneBtn);

            var list = document.createElement('div');
            list.className = 'dt-popover__list';

            var actions = document.createElement('div');
            actions.className = 'dt-popover__actions dt-popover__actions--footer';
            var apply = document.createElement('button');
            apply.type = 'button'; apply.className = 'btn btn--primary'; apply.textContent = 'Použít';
            var clear = document.createElement('button');
            clear.type = 'button'; clear.className = 'btn'; clear.textContent = 'Vymazat';
            actions.appendChild(apply);
            actions.appendChild(clear);

            pop.appendChild(pSearch);
            pop.appendChild(actionsTop);
            pop.appendChild(list);
            pop.appendChild(actions);
            wrap.appendChild(pop);

            function distinctValues() {
                var seen = Object.create(null);
                var out = [];
                allRows.forEach(function (r) {
                    var v = text(r.cells[idx]);
                    if (!(v in seen)) { seen[v] = true; out.push(v); }
                });
                out.sort(function (a, b) { return String(a).localeCompare(String(b), 'cs'); });
                return out;
            }

            function renderList() {
                list.innerHTML = '';
                var f = state.colFilters[idx];
                var selected = f && f.set ? f.set : null;
                var q = pSearch.value.trim().toLowerCase();
                distinctValues().forEach(function (v) {
                    if (q && v.toLowerCase().indexOf(q) === -1) { return; }
                    var row = document.createElement('label');
                    row.className = 'dt-check';
                    var cb = document.createElement('input');
                    cb.type = 'checkbox';
                    cb.value = v;
                    cb.checked = selected ? selected.has(v) : true;
                    row.appendChild(cb);
                    var span = document.createElement('span');
                    span.textContent = v === '' ? '(prázdné)' : v;
                    row.appendChild(span);
                    list.appendChild(row);
                });
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = pop.hidden;
                closeAllPopovers();
                if (open) {
                    var f = state.colFilters[idx];
                    pSearch.value = f && f.text ? f.text : '';
                    renderList();
                    pop.hidden = false;
                }
            });
            pop.addEventListener('click', function (e) { e.stopPropagation(); });
            pSearch.addEventListener('input', renderList);
            allBtn.addEventListener('click', function () {
                list.querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = true; });
            });
            noneBtn.addEventListener('click', function () {
                list.querySelectorAll('input[type=checkbox]').forEach(function (c) { c.checked = false; });
            });
            apply.addEventListener('click', function () {
                var boxes = Array.prototype.slice.call(list.querySelectorAll('input[type=checkbox]'));
                var total = distinctValues().length;
                var checked = boxes.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
                var txt = pSearch.value.trim().toLowerCase();
                // Kdyz je vybrano vse (a bez textu), filtr nema smysl -> zrusit.
                var fullSelection = pSearch.value.trim() === '' && checked.length === total;
                if (fullSelection && txt === '') {
                    delete state.colFilters[idx];
                } else {
                    state.colFilters[idx] = { set: checked.length === total && txt !== '' ? null : new Set(checked), text: txt };
                }
                btn.classList.toggle('is-active', !!state.colFilters[idx]);
                pop.hidden = true;
                state.page = 1;
                render();
            });
            clear.addEventListener('click', function () {
                delete state.colFilters[idx];
                btn.classList.remove('is-active');
                pSearch.value = '';
                pop.hidden = true;
                state.page = 1;
                render();
            });

            return wrap;
        }

        function closeAllPopovers() {
            table.querySelectorAll('.dt-popover').forEach(function (p) { p.hidden = true; });
        }
        document.addEventListener('click', closeAllPopovers);

        function rowMatches(row) {
            if (state.search && row.textContent.toLowerCase().indexOf(state.search) === -1) { return false; }
            for (var idx in state.colFilters) {
                var f = state.colFilters[idx];
                var v = text(row.cells[+idx]);
                if (f.set && !f.set.has(v)) { return false; }
                if (f.text && v.toLowerCase().indexOf(f.text) === -1) { return false; }
            }
            return true;
        }

        function sortRows(rows) {
            if (state.sortIdx < 0) { return rows; }
            var type = headers[state.sortIdx].getAttribute('data-type') || 'text';
            var asc = state.sortAsc;
            return rows.sort(function (a, b) {
                var av = cellSortValue(a, state.sortIdx), bv = cellSortValue(b, state.sortIdx);
                if (type === 'num') {
                    av = parseFloat(av); bv = parseFloat(bv);
                    if (isNaN(av)) { av = -Infinity; }
                    if (isNaN(bv)) { bv = -Infinity; }
                    return asc ? av - bv : bv - av;
                }
                return asc
                    ? String(av).localeCompare(String(bv), 'cs')
                    : String(bv).localeCompare(String(av), 'cs');
            });
        }

        function updateSortIndicators() {
            headers.forEach(function (th, i) {
                var a = th.querySelector('.dt-sort-asc');
                var d = th.querySelector('.dt-sort-desc');
                if (a) { a.classList.toggle('is-active', i === state.sortIdx && state.sortAsc); }
                if (d) { d.classList.toggle('is-active', i === state.sortIdx && !state.sortAsc); }
            });
        }

        function renderPager(totalRows, pages) {
            pager.innerHTML = '';
            if (pages <= 1) { return; }
            var add = function (label, page, opts) {
                opts = opts || {};
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'dt-page' + (opts.active ? ' is-active' : '');
                b.innerHTML = label;
                if (opts.disabled) { b.disabled = true; }
                else { b.addEventListener('click', function () { state.page = page; render(); }); }
                pager.appendChild(b);
            };
            add('&laquo;', 1, { disabled: state.page === 1 });
            add('&lsaquo;', state.page - 1, { disabled: state.page === 1 });

            var win = 2;
            var start = Math.max(1, state.page - win);
            var end = Math.min(pages, state.page + win);
            if (start > 1) { add('1', 1, {}); if (start > 2) { addEllipsis(); } }
            for (var p = start; p <= end; p++) { add(String(p), p, { active: p === state.page }); }
            if (end < pages) { if (end < pages - 1) { addEllipsis(); } add(String(pages), pages, {}); }

            add('&rsaquo;', state.page + 1, { disabled: state.page === pages });
            add('&raquo;', pages, { disabled: state.page === pages });

            function addEllipsis() {
                var s = document.createElement('span');
                s.className = 'dt-ellipsis';
                s.textContent = '…';
                pager.appendChild(s);
            }
        }

        function render() {
            var filtered = sortRows(allRows.filter(rowMatches));
            var per = state.perPage === Infinity ? (filtered.length || 1) : state.perPage;
            var pages = Math.max(1, Math.ceil(filtered.length / per));
            if (state.page > pages) { state.page = pages; }
            var start = (state.page - 1) * per;
            var end = start + per;

            allRows.forEach(function (r) { r.style.display = 'none'; });
            var slice = filtered.slice(start, end);
            slice.forEach(function (r) { r.style.display = ''; });
            // Poradi radku v DOM = poradi po razeni (jen viditelne kvuli vykonu).
            slice.forEach(function (r) { tbody.appendChild(r); });

            updateSortIndicators();
            renderPager(filtered.length, pages);
            info.textContent = filtered.length === 0
                ? 'Žádné záznamy'
                : (start + 1) + '–' + Math.min(end, filtered.length) + ' z ' + filtered.length;
        }

        var searchTimer;
        search.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                state.search = search.value.trim().toLowerCase();
                state.page = 1;
                render();
            }, 150);
        });
        ppSelect.addEventListener('change', function () {
            state.perPage = ppSelect.value === 'all' ? Infinity : (parseInt(ppSelect.value, 10) || 25);
            state.page = 1;
            render();
        });

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table[data-datatable]').forEach(init);
    });
})();

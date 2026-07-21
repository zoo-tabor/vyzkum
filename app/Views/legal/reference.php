<?php
/**
 * Verejna PR stranka /reference - poutac k zapojeni do vyzkumu dlouhovekosti psu.
 * Bez prihlaseni (_layout=public). Ukazky vyzkumne cinnosti + obrazky (lokalne v
 * public/assets/reference, zdroj zootabor.eu/vyzkumna-cinnost). Obsah cesky (PR,
 * odkazuje na ceske clanky) - zamerne neni obaleny do t().
 */
?>
<div class="reference">

    <section class="ref-hero card">
        <h1>Zapojte se do výzkumu dlouhověkosti psů</h1>
        <p class="ref-lead">
            ZOO Tábor dlouhodobě zkoumá, čím je ovlivněna délka života zvířat na molekulárně-genetické
            úrovni. U psů hledáme faktory dlouhověkosti napříč plemeny – a vaše účast nám v tom pomáhá.
            Získaná data mohou přispět k chovu zdravějších a dlouhověkých linií.
        </p>
        <p class="ref-gift">🎟️ <strong>Dárek za zapojení:</strong> jednorázová rodinná vstupenka do ZOO Tábor.</p>
    </section>

    <div class="ref-two">
        <section class="card">
            <h2>Co nabízíme</h2>
            <ul class="ref-list">
                <li>Účast na reálném vědeckém výzkumu dlouhověkosti psů.</li>
                <li>Genetické a zdravotní výsledky vašeho psa v přehledném online portálu.</li>
                <li>Za zapojení dárek – jednorázovou rodinnou vstupenku do ZOO Tábor.</li>
                <li>Vaše osobní údaje slouží výhradně výzkumu, nesdílíme je mimo něj.</li>
            </ul>
        </section>
        <section class="card">
            <h2>Co pro to musíte udělat</h2>
            <ul class="ref-list">
                <li>Zaregistrovat psa – nejsnáz naskenováním QR kódu z odběrové sady od veterináře.</li>
                <li>Nechat u veterináře odebrat vzorek (bukální stěr nebo krev).</li>
                <li>Vyplnit krátký dotazník o vašem psovi.</li>
                <li>Čas od času potvrdit, že údaje platí, a nahlásit případné změny.</li>
            </ul>
        </section>
    </div>

    <section class="card">
        <h2>Naše výzkumná činnost</h2>
        <p class="muted">Ukázky z naší dosavadní výzkumné činnosti (zdroj: <a href="https://www.zootabor.eu/vyzkumna-cinnost/" target="_blank" rel="noopener">zootabor.eu</a>).</p>
        <div class="ref-articles">
            <a class="ref-article" href="https://www.zootabor.eu/prumerny-vek-psu-plemene-cane-corso-je-ve-vztahu-se-zbarvenim-srsti/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/cc-v.jpg')) ?>" alt="Cane Corso" loading="lazy">
                <div class="ref-article__body">
                    <h3>Cane Corso: věk a zbarvení srsti</h3>
                    <p>Analyzovali jsme, zda průměrný věk dožití plemene Cane Corso souvisí se zbarvením jeho srsti.</p>
                    <span class="ref-more">Číst více →</span>
                </div>
            </a>
            <a class="ref-article" href="https://www.zootabor.eu/vyzkumna-cinnost-zubr/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/bison-v.jpg')) ?>" alt="Zubr evropský" loading="lazy">
                <div class="ref-article__body">
                    <h3>Zubr evropský</h3>
                    <p>Studium faktorů dlouhověkosti a genetické rozmanitosti u zubra evropského.</p>
                    <span class="ref-more">Číst více →</span>
                </div>
            </a>
            <a class="ref-article" href="https://www.zootabor.eu/vyzkum-dlouhovekosti-nosala-cerveneho/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/nosal-v.jpg')) ?>" alt="Nosál červený" loading="lazy">
                <div class="ref-article__body">
                    <h3>Nosál červený</h3>
                    <p>Výzkum délky života nosála červeného v podmínkách zoologické zahrady.</p>
                    <span class="ref-more">Číst více →</span>
                </div>
            </a>
        </div>
    </section>

    <footer class="ref-contact card">
        <h2>Kontakt</h2>
        <p>
            E-mail: <a href="mailto:vyzkum@zootabor.eu">vyzkum@zootabor.eu</a><br>
            Telefon: <a href="tel:+420233372021">(+420) 233 372 021</a><br>
            Web: <a href="https://vyzkum.zootabor.eu">vyzkum.zootabor.eu</a>
        </p>
    </footer>
</div>

<style>
.reference { max-width: 960px; margin: 0 auto; }
.reference h1 { margin-top: 0; }
.reference h2 { margin-top: 0; }
.ref-hero { text-align: center; }
.ref-lead { font-size: 1.05rem; line-height: 1.6; max-width: 720px; margin: 0.75rem auto; }
.ref-gift { display: inline-block; margin: 0.5rem auto 0; padding: 0.55rem 1rem; border-radius: 10px;
    background: #fff6da; border: 1px solid #ecd98a; font-size: 1rem; }
.ref-two { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.ref-list { margin: 0.4rem 0 0; padding-left: 1.1rem; line-height: 1.7; }
.ref-articles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 0.75rem; }
.ref-article { display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: 12px;
    overflow: hidden; text-decoration: none; color: var(--ink); background: var(--panel); transition: box-shadow .15s, transform .15s; }
.ref-article:hover { box-shadow: 0 6px 20px rgba(0,0,0,.10); transform: translateY(-2px); }
.ref-article img { width: 100%; aspect-ratio: 1/1; object-fit: cover; display: block; }
.ref-article__body { padding: 0.75rem 0.85rem 0.95rem; }
.ref-article__body h3 { margin: 0 0 0.35rem; font-size: 1.02rem; }
.ref-article__body p { margin: 0 0 0.5rem; font-size: 0.92rem; line-height: 1.45; color: var(--muted); }
.ref-more { font-weight: 600; color: var(--brand); font-size: 0.9rem; }
.ref-contact { text-align: center; }
.ref-contact p { line-height: 1.8; margin: 0.3rem 0 0; }
@media (max-width: 720px) {
    .ref-two { grid-template-columns: 1fr; }
    .ref-articles { grid-template-columns: 1fr; }
}
</style>

<?php
/**
 * Verejna PR stranka /reference - poutac k zapojeni do vyzkumu dlouhovekosti psu.
 * Bez prihlaseni (_layout=public). Prelozitelna pres t() - jazyk resi lang_switch
 * ve verejnem layoutu (session/cookie/Accept-Language). Obrazky lokalne v
 * public/assets/reference (CSP blokuje externi img). Odkazy na publikace vedou ven.
 */
?>
<div class="reference">

    <section class="ref-hero card">
        <h1><?= t('Zapojte se do výzkumu dlouhověkosti psů') ?></h1>
        <p class="ref-lead">
            <?= t('ZOO Tábor dlouhodobě zkoumá, čím je ovlivněna délka života zvířat na molekulárně-genetické úrovni. U psů hledáme faktory dlouhověkosti napříč plemeny – a vaše účast nám v tom pomáhá. Získaná data mohou přispět k chovu zdravějších a dlouhověkých linií.') ?>
        </p>
        <p class="ref-gift">🎟️ <strong><?= t('Dárek za zapojení:') ?></strong> <?= t('jednorázová rodinná vstupenka do ZOO Tábor.') ?></p>
    </section>

    <div class="ref-two">
        <section class="card">
            <h2><?= t('Co nabízíme') ?></h2>
            <ul class="ref-list">
                <li><?= t('Účast na reálném vědeckém výzkumu dlouhověkosti psů.') ?></li>
                <li><?= t('Genetické a zdravotní výsledky vašeho psa v přehledném online portálu.') ?></li>
                <li><?= t('Za zapojení dárek – jednorázovou rodinnou vstupenku do ZOO Tábor.') ?></li>
                <li><?= t('Vaše osobní údaje slouží výhradně výzkumu, nesdílíme je mimo něj.') ?></li>
            </ul>
        </section>
        <section class="card">
            <h2><?= t('Co pro to musíte udělat') ?></h2>
            <ul class="ref-list">
                <li><?= t('Zaregistrovat psa – nejsnáz naskenováním QR kódu z odběrové sady od veterináře.') ?></li>
                <li><?= t('Nechat u veterináře odebrat vzorek (bukální stěr nebo krev).') ?></li>
                <li><?= t('Vyplnit krátký dotazník o vašem psovi.') ?></li>
                <li><?= t('Čas od času potvrdit, že údaje platí, a nahlásit případné změny.') ?></li>
            </ul>
        </section>
    </div>

    <section class="card">
        <h2><?= t('Vědecké publikace') ?></h2>
        <p class="muted"><?= t('Recenzované studie našeho týmu o genetice dlouhověkosti psů.') ?></p>

        <a class="ref-pub ref-pub--feature" href="https://pmc.ncbi.nlm.nih.gov/articles/PMC11737349/" target="_blank" rel="noopener">
            <span class="ref-pub__badge"><?= t('Hlavní plemeno výzkumu') ?></span>
            <h3><?= t('Kavalír King Charles španěl: 9 genů dlouhověkosti') ?></h3>
            <p><?= t('Celogenomová asociační studie (GWAS) popsala devět kandidátních genů spojených s dlouhověkostí u kavalírů, kteří se dožili více než 13 let.') ?></p>
            <span class="ref-pub__meta">2024 · <?= t('Číst studii') ?> →</span>
        </a>

        <div class="ref-pubs">
            <a class="ref-pub" href="https://bmcvetres.biomedcentral.com/articles/10.1186/s12917-022-03290-9" target="_blank" rel="noopener">
                <h3><?= t('Cane Corso: čtyři nové geny dlouhověkosti') ?></h3>
                <p><?= t('U dlouhověkých jedinců plemene Cane Corso jsme identifikovali čtyři geny (TDRP, MC2R, FBXO25 a FBXL21) související s délkou života.') ?></p>
                <span class="ref-pub__meta">2022 · <?= t('Číst studii') ?> →</span>
            </a>
            <a class="ref-pub" href="https://www.koreccorso.cz/wp-content/uploads/2017/12/OVJ-2017-02-036-E.-Korec-et-al.pdf" target="_blank" rel="noopener">
                <h3><?= t('Cane Corso: dlouhověkost a zbarvení srsti') ?></h3>
                <p><?= t('Studie vztahu mezi průměrným dožitým věkem plemene Cane Corso a zbarvením jeho srsti.') ?></p>
                <span class="ref-pub__meta">2017 · <?= t('Číst studii') ?> →</span>
            </a>
        </div>

        <p class="ref-hub muted">
            <?= t('Kompletní přehled publikací:') ?>
            <a href="https://www.koreccorso.cz/vedecke/" target="_blank" rel="noopener"><?= t('vědecké práce') ?></a> ·
            <a href="https://www.koreccorso.cz/popularne-naucne/" target="_blank" rel="noopener"><?= t('popularizační články a média') ?></a>
        </p>
    </section>

    <section class="card">
        <h2><?= t('Naše výzkumná činnost') ?></h2>
        <p class="muted"><?= t('Ukázky z naší dosavadní výzkumné činnosti.') ?> <?= t('Zdroj:') ?> <a href="https://www.zootabor.eu/vyzkumna-cinnost/" target="_blank" rel="noopener">zootabor.eu</a></p>
        <div class="ref-articles">
            <a class="ref-article" href="https://www.zootabor.eu/prumerny-vek-psu-plemene-cane-corso-je-ve-vztahu-se-zbarvenim-srsti/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/cc-v.jpg')) ?>" alt="Cane Corso" loading="lazy">
                <div class="ref-article__body">
                    <h3><?= t('Cane Corso: věk a zbarvení srsti') ?></h3>
                    <p><?= t('Analyzovali jsme, zda průměrný věk dožití plemene Cane Corso souvisí se zbarvením jeho srsti.') ?></p>
                    <span class="ref-more"><?= t('Číst více') ?> →</span>
                </div>
            </a>
            <a class="ref-article" href="https://www.zootabor.eu/vyzkumna-cinnost-zubr/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/bison-v.jpg')) ?>" alt="Zubr evropský" loading="lazy">
                <div class="ref-article__body">
                    <h3><?= t('Zubr evropský') ?></h3>
                    <p><?= t('Studium faktorů dlouhověkosti a genetické rozmanitosti u zubra evropského.') ?></p>
                    <span class="ref-more"><?= t('Číst více') ?> →</span>
                </div>
            </a>
            <a class="ref-article" href="https://www.zootabor.eu/vyzkum-dlouhovekosti-nosala-cerveneho/" target="_blank" rel="noopener">
                <img src="<?= e(asset('assets/reference/nosal-v.jpg')) ?>" alt="Nosál červený" loading="lazy">
                <div class="ref-article__body">
                    <h3><?= t('Nosál červený') ?></h3>
                    <p><?= t('Výzkum délky života nosála červeného v podmínkách zoologické zahrady.') ?></p>
                    <span class="ref-more"><?= t('Číst více') ?> →</span>
                </div>
            </a>
        </div>
    </section>

    <footer class="ref-contact card">
        <h2><?= t('Kontakt') ?></h2>
        <p>
            <?= t('E-mail:') ?> <a href="mailto:vyzkum@zootabor.eu">vyzkum@zootabor.eu</a><br>
            <?= t('Telefon:') ?> <a href="tel:+420233372021">(+420) 233 372 021</a><br>
            <?= t('Web:') ?> <a href="https://vyzkum.zootabor.eu">vyzkum.zootabor.eu</a>
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

/* Vedecke publikace */
.ref-pub { display: block; border: 1px solid var(--line); border-radius: 12px; padding: 0.85rem 1rem;
    text-decoration: none; color: var(--ink); background: var(--panel); transition: box-shadow .15s, transform .15s; }
.ref-pub:hover { box-shadow: 0 6px 20px rgba(0,0,0,.10); transform: translateY(-2px); }
.ref-pub h3 { margin: 0 0 0.3rem; font-size: 1.05rem; }
.ref-pub p { margin: 0 0 0.45rem; font-size: 0.94rem; line-height: 1.5; color: var(--muted); }
.ref-pub__meta { font-weight: 600; color: var(--brand); font-size: 0.88rem; }
.ref-pub--feature { margin: 0.75rem 0 0; border-color: var(--brand); border-width: 2px; background: #f1f8f3; }
.ref-pub__badge { display: inline-block; margin-bottom: 0.4rem; padding: 0.15rem 0.6rem; border-radius: 999px;
    background: var(--brand); color: var(--brand-ink); font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
.ref-pubs { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 0.75rem; }
.ref-hub { margin: 0.9rem 0 0; }

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
    .ref-pubs { grid-template-columns: 1fr; }
    .ref-articles { grid-template-columns: 1fr; }
}
</style>

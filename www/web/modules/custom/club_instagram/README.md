# Club Instagram

Instagram panely pro klubový web. Příspěvky se spravují v administraci
jako obsah, panely je jen zobrazují – žádné API ani tokeny.

## Instalace

```
drush en club_instagram && drush cr
```

Pak *Konfigurace › Webové služby › Instagram klubu* – vyplnit účet (např. `skvinarice`).

Modul vytvoří:

- typ obsahu **Instagram příspěvek** (`instagram_post`) – Fotka, Odkaz na
  příspěvek, Popisek, Tým, Zápas,
- styly obrázků `club_instagram_square` (640 × 640) a
  `club_instagram_portrait` (600 × 750), obojí do WebP,
- blok **Instagram panel** (kategorie Footbal Platform),
- paragraf **Instagram panel** (`instagram_panel`) se stejnými možnostmi
  jako blok.

Pole Tým a Zápas se vytvoří, jen pokud existují typy obsahu `team` a `match`.
Paragraf se vytvoří, jen když je zapnutý modul Paragraphs (i dodatečně).

## Přidání příspěvku

*Obsah › Přidat obsah › Instagram příspěvek* – nahrát fotku, vložit odkaz
(na Instagramu Sdílet › Kopírovat odkaz). Pořadí v panelech je podle data
vytvoření, takže starší příspěvek lze posunout změnou data.

Detail příspěvku návštěvník neuvidí – je přesměrován rovnou na Instagram.

## Vzhledy panelu

| Vzhled | Kam se hodí | Fotek |
|---|---|---|
| `banner` – Promo banner | homepage, přes celou šířku | 4 |
| `grid` – Mřížka příspěvků | boční sloupec, sekce | 4 (od 5 fotek 3 sloupce) |
| `card` – Kompaktní karta | boční sloupec, detail zápasu | 1 |
| `profile` – Karta profilu | sekce Partneři, kontakty | 0 (logo z tématu) |
| `strip` – Spodní lišta | nad patičkou | 3 |

Nadpis, text, tlačítko i počet fotek jde v bloku přepsat.

## Které příspěvky se ukážou

- **Všechny** – nejnovější příspěvky.
- **Aktuální tým** – podle stránky / přepínače týmů (`club_core`). Nejdřív
  příspěvky týmu, zbytek se doplní klubovými (bez týmu).
- **Jen tým X** – pevně zvolený tým.
- **Na detailu zápasu** (zaškrtnuto výchozí) – nejdřív příspěvky s vyplněným
  polem Zápas pro ten zápas.

## Použití

**Paragraf:** typ „Instagram panel“ povolte v nastavení pole s paragrafy
(např. Základní stránka › `field_page_c` nebo `field_page_sidebar`,
*Spravovat pole › Upravit › Typy odkazovaných paragrafů*). Jeho pole jsou jen
nastavení panelu (vzhled, nadpis, text, tlačítko, počet fotek, které
příspěvky, zápas, jiný účet), vykreslí se stejný panel jako u bloku.
Do bočního panelu se hodí vzhled Karta nebo Profil.

Na webu, kde už je modul zapnutý, paragraf vytvoří `drush updb`.


**Blokové rozvržení / Layout Builder:** Umístit blok › Instagram panel.

**Přímo v šabloně** (Twig Tweak), např. v `node--match--full.html.twig`:

```twig
{{ drupal_block('club_instagram_panel', {variant: 'card'}) }}
{{ drupal_block('club_instagram_panel', {variant: 'strip', count: 4, team: 'context'}) }}
{{ drupal_block('club_instagram_panel', {variant: 'profile', handle: 'jiny_klub'}) }}
```

Klíče: `variant`, `heading`, `text`, `button`, `count`, `team`
(`all` | `context` | ID týmu), `match` (true/false), `handle`.

## Vzhled

Logika panelu je ve službě `club_instagram.panel_builder`
(`PanelBuilder`), blok i paragraf ji jen volají.

Styly jsou v `css/instagram-panel.css` (čisté CSS, není potřeba buildit
Tailwind). Barvy bere z proměnných tématu Club (`--club-red`, `--club-blue`…),
takže odpovídají nastavení barev a fungují i v tmavém režimu.

Šablonu lze v tématu přepsat celou (`club-instagram-panel.html.twig`) nebo
jen pro jeden vzhled (`club-instagram-panel--strip.html.twig`).

Ikona je obecný fotoaparát. Pokud chcete oficiální logo Instagramu,
stáhněte ho z Meta Brand Resources a nahraďte `{% set icon %}` v šabloně.

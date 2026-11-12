# SKBL – téma webu SK Baník Libušín

Tailwind CSS 4 + Single Directory Components. Navazuje na modul `skbl_core`
(kontext týmu, přepínač týmů, Views argument).

## Instalace

1. Složku `skbl` nahrajte do `web/themes/custom/skbl`.
2. `drush theme:enable skbl && drush config:set system.theme default skbl`
3. `drush cr`

Zkompilované `css/skbl.css` je součástí balíčku, na serveru není potřeba Node.

## Úpravy CSS

```
npm install
npm run watch   # vývoj
npm run build   # před nasazením
```

Tailwind prohledává `templates/`, `components/`, `layouts/`, `skbl.theme`
a `modules/custom/skbl_core/templates`. Pokud máte modul jinde, upravte
cestu `@source` v `src/skbl.css`.

## Regiony

| Region | Co tam patří |
|---|---|
| Lišta akcí (`ticker`) | view `events`, display `ticker` |
| Hlavička – menu vlevo / vpravo | bloky menu (podmenu se rozbalují) |
| Přepínač týmů (`switcher`) | blok „Přepínač týmů“ ze skbl_core |
| Záhlaví stránky (`page_header`) | Drobečková navigace + Nadpis stránky |
| Obsah, Patička | obvyklé bloky |

## Rozvržení pro Layout Builder (kategorie SKBL)

- **Hero (zápas + akce)** – příští zápas a kalendář akcí
- **Sekce v kontejneru**, **Sekce na šedém pozadí**
- **Sekce přes celou šířku** – pro posuvnou soupisku
- **Dva sloupce 3 : 2**

## Třídy pro Views (pole „CSS class“ v nastavení view)

| Třída | Vzhled |
|---|---|
| `skbl-scroller` | posuvný pás se šipkami (soupiska) |
| `skbl-cards` | mřížka oddělená linkami (výsledky) |
| `skbl-grid` | mřížka karet (videotéka, aktuality) |
| `skbl-list` | seznam s linkami (kalendář akcí) |
| `skbl-stack` | sloupec s mezerami (další videa) |

## Šablony podle view modu

| Entita | View mode | Komponenta |
|---|---|---|
| Zápas (`match`) | `teaser` | result-card |
| Zápas (`match`) | `hero` *(vytvořit)* | match-hero |
| Turnaj (`tournament`) | `teaser` | result-card |
| Hráč (`player`) | `teaser` | player-card |
| Akce (`event`) | `teaser` | event-item |
| Akce (`event`) | `ticker` *(vytvořit)* | řádek lišty |
| Video (`video`) | `teaser` | video-card |
| Video (`video`) | `featured` *(vytvořit)* | video-card velké |
| Video (`video`) | `full` | přehrávač / tlačítko na VEO |
| Tým (`team`) | `full` | pruh společného týmu + Layout Builder |

Ve views používejte formát *Rendered entity* s příslušným view modem.

Šablony počítají s poli z navrženého datového modelu (`field_team`,
`field_date`, `field_opponent`, `field_is_home`, `field_score_us`,
`field_score_them`, `field_venue`, `field_competition`, `field_first_name`,
`field_last_name`, `field_photo`, `field_home_club`, `field_partner_club`,
`field_display_name`, `field_short_name`, `field_logo`, `field_video_category`,
`field_duration`, `field_video_url`, `field_thumbnail`, `field_match`…). Chybějící pole se přeskočí, šablona nespadne.

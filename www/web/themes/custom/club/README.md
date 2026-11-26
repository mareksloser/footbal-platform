# Club – téma klubového webu Footbal Platform

Tailwind CSS 4 + Single Directory Components. Navazuje na modul `club_core`
(kontext týmu, přepínač týmů, Views argument).

## Instalace

1. Složku `club` nahrajte do `web/themes/custom/club`.
2. `drush theme:enable club && drush config:set system.theme default club`
3. `drush cr`

Zkompilované `css/club.css` je součástí balíčku, na serveru není potřeba Node.

## Úpravy CSS

```
npm install
npm run watch   # vývoj
npm run build   # před nasazením
```

Tailwind prohledává `templates/`, `components/`, `layouts/`, `club.theme`
a `modules/custom/club_core/templates`. Pokud máte modul jinde, upravte
cestu `@source` v `src/club.css`.

## Nastavení tématu

Vzhled › Nastavení › Club:

- **Klub** – název klubu, krátký název (zvýraznění v tabulkách), tlačítko
  v hlavičce, údaje do patičky.
- **Barvy** – hlavní a doplňková barva (plus varianty pro text), barvy výhry
  a remízy, zvlášť pro světlý a tmavý režim. Ukládají se do `club.settings`
  (`colors.light.*`, `colors.dark.*`) a téma je vypíše jako inline `<style>`,
  který přepíše CSS proměnné `--club-*`. Není potřeba znovu buildit CSS.
- **Logo** – výchozí je neutrální `logo.svg`, vlastní logo nahrajte
  ve standardní sekci „Logo“.

## Regiony

| Region | Co tam patří |
|---|---|
| Lišta akcí (`ticker`) | view `events`, display `ticker` |
| Hlavička – menu vlevo / vpravo | bloky menu (podmenu se rozbalují) |
| Přepínač týmů (`switcher`) | blok „Přepínač týmů“ ze club_core |
| Záhlaví stránky (`page_header`) | Drobečková navigace + Nadpis stránky |
| Obsah, Patička | obvyklé bloky |

## Rozvržení pro Layout Builder (kategorie Club)

- **Hero (zápas + akce)** – příští zápas a kalendář akcí
- **Sekce v kontejneru**, **Sekce na šedém pozadí**
- **Sekce přes celou šířku** – pro posuvnou soupisku
- **Dva sloupce 3 : 2**

## Třídy pro Views (pole „CSS class“ v nastavení view)

| Třída | Vzhled |
|---|---|
| `club-scroller` | posuvný pás se šipkami (soupiska) |
| `club-cards` | mřížka oddělená linkami (výsledky) |
| `club-grid` | mřížka karet (videotéka, aktuality) |
| `club-list` | seznam s linkami (kalendář akcí) |
| `club-stack` | sloupec s mezerami (další videa) |
| `club-project-groups` | projekty po skupinách Probíhá / Plánujeme / Hotovo |

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
| Projekt (`project`) | `teaser` | project-card |
| Projekt (`project`) | `full` | detail se šedým záhlavím a kartou „O projektu“ |

Ve views používejte formát *Rendered entity* s příslušným view modem.

Šablony počítají s poli z navrženého datového modelu (`field_team`,
`field_date`, `field_opponent`, `field_is_home`, `field_score_us`,
`field_score_them`, `field_venue`, `field_competition`, `field_first_name`,
`field_last_name`, `field_photo`, `field_home_club`, `field_partner_club`,
`field_display_name`, `field_short_name`, `field_logo`, `field_video_category`,
`field_duration`, `field_video_url`, `field_thumbnail`, `field_match`…). Chybějící pole se přeskočí, šablona nespadne.

## Projekty (/projekty)

Typ obsahu **Projekt** zakládá `club_core` (`drush updb`, update 10002).

1. U typu Projekt přidejte *existující* pole **Perex** (`field_lead`),
   **Obrázek** (`field_image`) a **Text** (`field_body`). Pole **Podpořili**
   (`field_project_partners`) zapněte ve formuláři, pokud existuje slovník Partneři.
2. View `projects` (obsah typu Projekt, publikováno, view mode *teaser*,
   formát *Unformatted list*, řazení *Vytvořeno* sestupně):
   - display **Page** `/projekty`, CSS třída `club-project-groups` – skupiny podle
     stavu, pořadí skupin = pořadí hodnot pole Stav,
   - volitelně display **Block** „Co chystáme“ – filtr Stav = Probíhá + Plánujeme,
     3 položky, bez CSS třídy (mřížka karet), do Layout Builderu úvodní stránky.
3. Odkaz *Projekty* do hlavního menu.

Barvy štítků: Probíhá = hlavní barva, Plánujeme = doplňková, Hotovo = barva výhry
(všechny jdou změnit v nastavení tématu).

## Patička

Vrstvy (vše se skrývá, když chybí data):

1. **Náborový pruh** – *Vzhled › Nastavení › Club › Patička › Náborový pruh*.
   Hlavní tlačítko vede na *Odkaz tlačítka* z hlavičky.
2. **Pás partnerů „Podporují nás“** – partneři se zaškrtnutým *Zobrazit v patičce*
   (`field_partner_footer`, `drush updb` – club_core 10003), řazení podle váhy.
   Na úvodní stránce se nezobrazuje (má vlastní sekci partnerů).
3. **Hlavní část** – kontakt + IČO + sociální sítě (Instagram z modulu
   `club_instagram`, Facebook a YouTube z nastavení), **Týmy** automaticky podle
   `field_team_group`, **region Patička** (sem dejte blok menu „Patička“ s názvem
   „Klub“ – O klubu, Aktuality, Projekty, Partneři, Dokumenty…) a **Kde hrajeme**
   (areál, tréninky, tlačítko Navigovat).
4. **Spodní lišta** – © rok, poznámka, Ochrana osobních údajů, Cookies,
   Pro trenéry (/user), Nahoru.

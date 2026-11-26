<?php

/**
 * @file
 * Nastavení tématu Club (Vzhled › Nastavení › Club).
 */

use Drupal\Core\Form\FormStateInterface;

require_once __DIR__ . '/club.theme';

/**
 * Implements hook_form_FORM_ID_alter() for system_theme_settings.
 */
function club_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state): void {
  $form['club'] = [
    '#type' => 'details',
    '#title' => t('Klub'),
    '#open' => TRUE,
    '#weight' => -10,
  ];
  $form['club']['club_title'] = [
    '#type' => 'textfield',
    '#title' => t('Název klubu'),
    '#description' => t('Např. v pruhu společného týmu nebo jako mateřský klub hráče. Prázdné = název webu.'),
    '#default_value' => theme_get_setting('club_title'),
  ];
  $form['club']['club_short_name'] = [
    '#type' => 'textfield',
    '#title' => t('Krátký název klubu'),
    '#description' => t('Podle něj se zvýrazní náš řádek v tabulkách soutěží a turnajů. Prázdné = název klubu.'),
    '#default_value' => theme_get_setting('club_short_name'),
  ];
  $form['club']['cta_label'] = [
    '#type' => 'textfield',
    '#title' => t('Text tlačítka v hlavičce'),
    '#default_value' => theme_get_setting('cta_label'),
  ];
  $form['club']['cta_label_mobile'] = [
    '#type' => 'textfield',
    '#title' => t('Text tlačítka na mobilu'),
    '#default_value' => theme_get_setting('cta_label_mobile'),
  ];
  $form['club']['cta_url'] = [
    '#type' => 'textfield',
    '#title' => t('Odkaz tlačítka'),
    '#description' => t('Interní cesta (/nabor) nebo celá adresa. Prázdné = tlačítko se nezobrazí.'),
    '#default_value' => theme_get_setting('cta_url'),
  ];
  $form['club']['merged_label'] = [
    '#type' => 'textfield',
    '#title' => t('Štítek společného týmu v menu'),
    '#description' => t('Zobrazí se u odkazů na společné týmy v podmenu, např. „s partnerským klubem“.'),
    '#default_value' => theme_get_setting('merged_label') ?: 'společný tým',
  ];
  // Patička – hodnoty se ukládají ploše (club_name, club_phone…), skupina je jen vizuální.
  $form['footer'] = [
    '#type' => 'details',
    '#title' => t('Patička'),
    '#description' => t('Nevyplněné položky se v patičce nezobrazí.'),
    '#open' => TRUE,
    '#weight' => -9.5,
  ];
  $form['footer']['contact'] = [
    '#type' => 'fieldset',
    '#title' => t('Spolek a kontakt'),
  ];
  $form['footer']['contact']['club_name'] = [
    '#type' => 'textfield',
    '#title' => t('Název spolku'),
    '#default_value' => theme_get_setting('club_name'),
  ];
  $form['footer']['contact']['club_address'] = [
    '#type' => 'textarea',
    '#title' => t('Adresa sídla'),
    '#rows' => 3,
    '#default_value' => theme_get_setting('club_address'),
  ];
  $form['footer']['contact']['club_ico'] = [
    '#type' => 'textfield',
    '#title' => t('IČO'),
    '#size' => 12,
    '#default_value' => theme_get_setting('club_ico'),
  ];
  $form['footer']['contact']['club_email'] = [
    '#type' => 'email',
    '#title' => t('E-mail'),
    '#default_value' => theme_get_setting('club_email'),
  ];
  $form['footer']['contact']['club_phone'] = [
    '#type' => 'textfield',
    '#title' => t('Telefon'),
    '#size' => 20,
    '#default_value' => theme_get_setting('club_phone'),
  ];
  $form['footer']['contact']['social_facebook'] = [
    '#type' => 'url',
    '#title' => t('Facebook'),
    '#default_value' => theme_get_setting('social_facebook'),
  ];
  $form['footer']['contact']['social_youtube'] = [
    '#type' => 'url',
    '#title' => t('YouTube'),
    '#description' => t('Instagram se bere z nastavení modulu Instagram (je-li zapnutý).'),
    '#default_value' => theme_get_setting('social_youtube'),
  ];

  $form['footer']['recruit'] = [
    '#type' => 'fieldset',
    '#title' => t('Náborový pruh'),
    '#description' => t('Hlavní tlačítko vede na „Odkaz tlačítka“ z hlavičky. Bez textu se pruh nezobrazí.'),
  ];
  $form['footer']['recruit']['recruit_title'] = [
    '#type' => 'textfield',
    '#title' => t('Nadpis'),
    '#default_value' => theme_get_setting('recruit_title'),
    '#placeholder' => 'Přidej se k nám',
  ];
  $form['footer']['recruit']['recruit_text'] = [
    '#type' => 'textfield',
    '#title' => t('Text'),
    '#maxlength' => 255,
    '#default_value' => theme_get_setting('recruit_text'),
    '#placeholder' => 'Hledáme kluky i holky od 5 let. První tréninky si vyzkoušíš zdarma.',
  ];
  $form['footer']['recruit']['recruit_label'] = [
    '#type' => 'textfield',
    '#title' => t('Text hlavního tlačítka'),
    '#description' => t('Prázdné = text tlačítka v hlavičce.'),
    '#default_value' => theme_get_setting('recruit_label'),
  ];
  $form['footer']['recruit']['recruit_second_label'] = [
    '#type' => 'textfield',
    '#title' => t('Druhé tlačítko – text'),
    '#default_value' => theme_get_setting('recruit_second_label'),
    '#placeholder' => 'Kontakty na trenéry',
  ];
  $form['footer']['recruit']['recruit_second_url'] = [
    '#type' => 'textfield',
    '#title' => t('Druhé tlačítko – odkaz'),
    '#default_value' => theme_get_setting('recruit_second_url'),
    '#placeholder' => '/kontakty',
  ];

  $form['footer']['venue'] = [
    '#type' => 'fieldset',
    '#title' => t('Kde hrajeme'),
  ];
  $form['footer']['venue']['venue_address'] = [
    '#type' => 'textarea',
    '#title' => t('Areál'),
    '#rows' => 2,
    '#default_value' => theme_get_setting('venue_address'),
    '#placeholder' => "Fotbalový areál\nU Hřiště 245, Libušín",
  ];
  $form['footer']['venue']['venue_map_url'] = [
    '#type' => 'url',
    '#title' => t('Odkaz do map (tlačítko Navigovat)'),
    '#description' => t('Např. sdílený odkaz z Mapy.cz.'),
    '#default_value' => theme_get_setting('venue_map_url'),
  ];
  $form['footer']['venue']['training_note'] = [
    '#type' => 'textarea',
    '#title' => t('Tréninky'),
    '#rows' => 2,
    '#default_value' => theme_get_setting('training_note'),
    '#placeholder' => "Mládež: út a čt 16:30–18:00",
  ];

  $form['footer']['bottom'] = [
    '#type' => 'fieldset',
    '#title' => t('Spodní lišta a partneři'),
  ];
  $form['footer']['bottom']['footer_note'] = [
    '#type' => 'textfield',
    '#title' => t('Poznámka za copyrightem'),
    '#default_value' => theme_get_setting('footer_note'),
  ];
  $form['footer']['bottom']['privacy_url'] = [
    '#type' => 'textfield',
    '#title' => t('Ochrana osobních údajů – odkaz'),
    '#default_value' => theme_get_setting('privacy_url'),
    '#placeholder' => '/ochrana-osobnich-udaju',
  ];
  $form['footer']['bottom']['cookies_url'] = [
    '#type' => 'textfield',
    '#title' => t('Cookies – odkaz'),
    '#default_value' => theme_get_setting('cookies_url'),
  ];
  $form['footer']['bottom']['partner_cta_url'] = [
    '#type' => 'textfield',
    '#title' => t('„Staňte se partnerem“ – odkaz'),
    '#description' => t('Pás partnerů ukazuje partnery se zaškrtnutým „Zobrazit v patičce“; na úvodní stránce se skrývá.'),
    '#default_value' => theme_get_setting('partner_cta_url'),
  ];

  // Barvy – uloží se jako colors.light.<klíč> a colors.dark.<klíč>.
  $saved = theme_get_setting('colors');
  $saved = is_array($saved) ? $saved : [];
  $form['colors'] = [
    '#type' => 'details',
    '#title' => t('Barvy'),
    '#description' => t('Barvy webu ve světlém a tmavém režimu. Tlačítko „Obnovit výchozí barvy“ vrátí původní paletu.'),
    '#open' => TRUE,
    '#weight' => -9,
    '#tree' => TRUE,
  ];
  $modes = [
    'light' => t('Světlý režim'),
    'dark' => t('Tmavý režim'),
  ];
  foreach ($modes as $mode => $mode_label) {
    $form['colors'][$mode] = [
      '#type' => 'details',
      '#title' => $mode_label,
      '#open' => $mode === 'light',
    ];
  }
  foreach (_club_color_definitions() as $key => [$label, $default_light, $default_dark]) {
    foreach (['light' => $default_light, 'dark' => $default_dark] as $mode => $default) {
      $value = strtolower((string) ($saved[$mode][$key] ?? ''));
      $form['colors'][$mode][$key] = [
        '#type' => 'color',
        '#title' => $label,
        '#default_value' => preg_match('/^#[0-9a-f]{6}$/', $value) ? $value : $default,
        '#description' => t('Výchozí: @color', ['@color' => $default]),
      ];
    }
  }
  $form['colors']['reset'] = [
    '#type' => 'submit',
    '#value' => t('Obnovit výchozí barvy'),
    '#submit' => ['_club_theme_settings_reset_colors'],
    '#limit_validation_errors' => [],
  ];
}

/**
 * Submit handler: smaže uložené barvy, web použije výchozí paletu z CSS.
 */
function _club_theme_settings_reset_colors(array &$form, FormStateInterface $form_state): void {
  \Drupal::configFactory()->getEditable('club.settings')->clear('colors')->save();
  \Drupal::messenger()->addStatus(t('Barvy byly obnoveny na výchozí.'));
}

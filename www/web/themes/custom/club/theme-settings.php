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
  $form['club']['club_name'] = [
    '#type' => 'textfield',
    '#title' => t('Název spolku v patičce'),
    '#default_value' => theme_get_setting('club_name'),
  ];
  $form['club']['club_address'] = [
    '#type' => 'textarea',
    '#title' => t('Adresa'),
    '#rows' => 3,
    '#default_value' => theme_get_setting('club_address'),
  ];
  $form['club']['club_email'] = [
    '#type' => 'email',
    '#title' => t('E-mail'),
    '#default_value' => theme_get_setting('club_email'),
  ];
  $form['club']['footer_note'] = [
    '#type' => 'textfield',
    '#title' => t('Poznámka pod čarou v patičce'),
    '#default_value' => theme_get_setting('footer_note'),
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

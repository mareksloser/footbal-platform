<?php

/**
 * @file
 * Nastavení tématu SKBL (Vzhled › Nastavení › SKBL).
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for system_theme_settings.
 */
function skbl_form_system_theme_settings_alter(array &$form, FormStateInterface $form_state): void {
  $form['skbl'] = [
    '#type' => 'details',
    '#title' => t('SK Baník Libušín'),
    '#open' => TRUE,
    '#weight' => -10,
  ];
  $form['skbl']['cta_label'] = [
    '#type' => 'textfield',
    '#title' => t('Text tlačítka v hlavičce'),
    '#default_value' => theme_get_setting('cta_label'),
  ];
  $form['skbl']['cta_label_mobile'] = [
    '#type' => 'textfield',
    '#title' => t('Text tlačítka na mobilu'),
    '#default_value' => theme_get_setting('cta_label_mobile'),
  ];
  $form['skbl']['cta_url'] = [
    '#type' => 'textfield',
    '#title' => t('Odkaz tlačítka'),
    '#description' => t('Interní cesta (/nabor) nebo celá adresa. Prázdné = tlačítko se nezobrazí.'),
    '#default_value' => theme_get_setting('cta_url'),
  ];
  $form['skbl']['merged_label'] = [
    '#type' => 'textfield',
    '#title' => t('Štítek společného týmu v menu'),
    '#description' => t('Zobrazí se u odkazů na společné týmy v podmenu, např. „s Vinařicemi“.'),
    '#default_value' => theme_get_setting('merged_label') ?: 's Vinařicemi',
  ];
  $form['skbl']['club_name'] = [
    '#type' => 'textfield',
    '#title' => t('Název spolku v patičce'),
    '#default_value' => theme_get_setting('club_name'),
  ];
  $form['skbl']['club_address'] = [
    '#type' => 'textarea',
    '#title' => t('Adresa'),
    '#rows' => 3,
    '#default_value' => theme_get_setting('club_address'),
  ];
  $form['skbl']['club_email'] = [
    '#type' => 'email',
    '#title' => t('E-mail'),
    '#default_value' => theme_get_setting('club_email'),
  ];
  $form['skbl']['footer_note'] = [
    '#type' => 'textfield',
    '#title' => t('Poznámka pod čarou v patičce'),
    '#default_value' => theme_get_setting('footer_note'),
  ];
}

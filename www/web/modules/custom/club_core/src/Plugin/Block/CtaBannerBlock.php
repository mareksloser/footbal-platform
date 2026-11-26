<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Výzva – celošířkový pruh s nadpisem, textem a až dvěma tlačítky.
 *
 * Nábor, brigáda na hřišti, turnaj, hledáme trenéra… Lze vložit vícekrát.
 */
#[Block(
  id: 'club_cta_banner',
  admin_label: new TranslatableMarkup('Výzva (pruh s tlačítky)'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class CtaBannerBlock extends BlockBase {

  use LinkSettingTrait;

  public const STYLES = ['primary', 'secondary', 'dark', 'light'];

  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
      'heading' => '',
      'text' => '',
      'primary_label' => '',
      'primary_url' => '',
      'secondary_label' => '',
      'secondary_url' => '',
      'style' => 'primary',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $c = $this->configuration;
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nadpis'),
      '#default_value' => $c['heading'],
      '#placeholder' => 'Přidej se k nám',
      '#required' => TRUE,
    ];
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#maxlength' => 255,
      '#default_value' => $c['text'],
      '#placeholder' => 'Hledáme kluky i holky od 5 let. První tréninky si vyzkoušíš zdarma.',
    ];
    $form['primary_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hlavní tlačítko – text'),
      '#default_value' => $c['primary_label'],
    ];
    $form['primary_url'] = $this->linkElement($this->t('Hlavní tlačítko – odkaz'), $c['primary_url']);
    $form['secondary_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Druhé tlačítko – text'),
      '#default_value' => $c['secondary_label'],
    ];
    $form['secondary_url'] = $this->linkElement($this->t('Druhé tlačítko – odkaz'), $c['secondary_url'], '/kontakty');
    $form['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Barva pruhu'),
      '#options' => [
        'primary' => $this->t('Hlavní barva klubu'),
        'secondary' => $this->t('Doplňková barva klubu'),
        'dark' => $this->t('Tmavá'),
        'light' => $this->t('Světle šedá'),
      ],
      '#default_value' => $c['style'],
    ];
    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state): void {
    $this->validateLink($form_state, 'primary_url');
    $this->validateLink($form_state, 'secondary_url');
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      if ($key !== 'label_display') {
        $this->configuration[$key] = trim((string) $form_state->getValue($key));
      }
    }
  }

  public function build(): array {
    $c = $this->configuration;
    $buttons = [];
    foreach (['primary', 'secondary'] as $key) {
      $url = self::linkUrl($c[$key . '_url']);
      if ($url && $c[$key . '_label'] !== '') {
        $buttons[] = ['label' => $c[$key . '_label'], 'url' => $url, 'primary' => $key === 'primary'];
      }
    }
    return [
      '#theme' => 'club_cta_banner',
      '#heading' => $c['heading'],
      '#text' => $c['text'],
      '#buttons' => $buttons,
      '#style' => in_array($c['style'], self::STYLES, TRUE) ? $c['style'] : 'primary',
    ];
  }

}

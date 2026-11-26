<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Místo – adresa, poznámka (tréninky, otevírací doba) a tlačítko do map.
 *
 * Nadpis je název bloku („Kde hrajeme“, „Kde trénujeme“, „Klubovna“…).
 */
#[Block(
  id: 'club_place',
  admin_label: new TranslatableMarkup('Místo (adresa a mapa)'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class PlaceBlock extends BlockBase {

  use LinkSettingTrait;

  public function defaultConfiguration(): array {
    return [
      'label' => 'Kde hrajeme',
      'label_display' => 'visible',
      'address' => '',
      'note' => '',
      'map_url' => '',
      'map_label' => 'Navigovat',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $c = $this->configuration;
    $form['address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Adresa'),
      '#rows' => 2,
      '#default_value' => $c['address'],
      '#placeholder' => "Fotbalový areál\nU Hřiště 245, Libušín",
    ];
    $form['note'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Poznámka'),
      '#description' => $this->t('Např. časy tréninků nebo otevírací doba.'),
      '#rows' => 2,
      '#default_value' => $c['note'],
    ];
    $form['map_url'] = $this->linkElement($this->t('Odkaz do map'), $c['map_url'], 'https://mapy.cz/s/…');
    $form['map_url']['#description'] = $this->t('Např. sdílený odkaz z Mapy.cz. Prázdné = bez tlačítka.');
    $form['map_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text tlačítka'),
      '#default_value' => $c['map_label'],
    ];
    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state): void {
    $this->validateLink($form_state, 'map_url');
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['address', 'note', 'map_url', 'map_label'] as $key) {
      $this->configuration[$key] = trim((string) $form_state->getValue($key));
    }
  }

  public function build(): array {
    $c = $this->configuration;
    if ($c['address'] === '' && $c['note'] === '') {
      return [];
    }
    return [
      '#theme' => 'club_place',
      '#address' => $c['address'],
      '#note' => $c['note'],
      '#map_url' => self::linkUrl($c['map_url']),
      '#map_label' => $c['map_label'] ?: $this->t('Navigovat'),
    ];
  }

}

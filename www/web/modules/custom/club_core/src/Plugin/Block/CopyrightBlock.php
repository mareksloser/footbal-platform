<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Copyright – „© rok Název webu. Poznámka“.
 */
#[Block(
  id: 'club_copyright',
  admin_label: new TranslatableMarkup('Copyright'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class CopyrightBlock extends BlockBase {

  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
      'owner' => '',
      'note' => '',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $form['owner'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Vlastník'),
      '#description' => $this->t('Prázdné = název webu.'),
      '#default_value' => $this->configuration['owner'],
    ];
    $form['note'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Poznámka za copyrightem'),
      '#default_value' => $this->configuration['note'],
      '#placeholder' => 'Fotbal pro všechny od roku 1931.',
    ];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['owner'] = trim((string) $form_state->getValue('owner'));
    $this->configuration['note'] = trim((string) $form_state->getValue('note'));
  }

  public function build(): array {
    return [
      '#theme' => 'club_copyright',
      '#year' => date('Y'),
      '#owner' => $this->configuration['owner'] ?: \Drupal::config('system.site')->get('name'),
      '#note' => $this->configuration['note'],
      '#cache' => ['tags' => ['config:system.site']],
    ];
  }

  public function getCacheMaxAge(): int {
    // Rok se mění jednou ročně – stačí den.
    return Cache::mergeMaxAges(parent::getCacheMaxAge(), 86400);
  }

}

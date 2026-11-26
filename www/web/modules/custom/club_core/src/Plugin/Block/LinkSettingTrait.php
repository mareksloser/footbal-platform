<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Odkazy v nastavení bloků: /interni-cesta nebo https://… .
 */
trait LinkSettingTrait {

  /**
   * Formulářový prvek pro odkaz.
   */
  protected function linkElement(string|\Stringable $title, ?string $value, string $placeholder = '/nabor'): array {
    return [
      '#type' => 'textfield',
      '#title' => $title,
      '#default_value' => $value,
      '#placeholder' => $placeholder,
      '#description' => $this->t('Interní cesta (/nabor) nebo celá adresa (https://…).'),
    ];
  }

  /**
   * Ověří odkaz z formuláře (prázdný je v pořádku).
   */
  protected function validateLink(FormStateInterface $form_state, string $key): void {
    $value = trim((string) $form_state->getValue($key));
    if ($value !== '' && self::linkUrl($value) === NULL) {
      $form_state->setErrorByName($key, $this->t('Odkaz musí začínat lomítkem (/stranka) nebo http(s)://.'));
    }
  }

  /**
   * Převede uložený odkaz na URL řetězec, neplatný = NULL.
   */
  protected static function linkUrl(?string $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
      return NULL;
    }
    try {
      if (str_starts_with($value, '/') || str_starts_with($value, '#')) {
        return Url::fromUserInput($value)->toString();
      }
      if (preg_match('~^https?://~i', $value)) {
        return Url::fromUri($value)->toString();
      }
    }
    catch (\InvalidArgumentException) {
    }
    return NULL;
  }

}

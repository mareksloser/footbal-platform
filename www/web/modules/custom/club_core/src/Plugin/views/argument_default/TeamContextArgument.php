<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\views\argument_default;

use Drupal\club_core\TeamContext;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Výchozí hodnota contextual filtru = aktuální tým webu.
 *
 * Vrací např. „15+1“ (tým + Celý klub), na úvodní stránce „all“.
 * V contextual filtru je potřeba zapnout „Allow multiple values“.
 */
#[ViewsArgumentDefault(
  id: 'club_team_context',
  title: new TranslatableMarkup('Aktuální tým (kontext webu)'),
)]
final class TeamContextArgument extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  private TeamContext $teamContext;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->teamContext = $container->get('club_core.team_context');
    return $instance;
  }

  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['include_club'] = ['default' => TRUE];
    $options['club_fallback'] = ['default' => ''];
    return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['include_club'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zahrnout i klubový obsah („Celý klub“)'),
      '#description' => $this->t('Zapněte u akcí, aktualit a videí. Vypněte u soupisky a výsledků.'),
      '#default_value' => $this->options['include_club'],
    ];
    $form['club_fallback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tým pro „Celý klub“'),
      '#description' => $this->t('Slug týmu, který se zobrazí bez filtru (např. a-tym pro soupisku na úvodní stránce). Prázdné = vše.'),
      '#default_value' => $this->options['club_fallback'],
      '#size' => 16,
    ];
  }

  public function getArgument() {
    $team = $this->teamContext->getTeam();

    if (!$team) {
      $fallback = trim((string) $this->options['club_fallback']);
      if ($fallback !== '' && ($fallbackTeam = $this->teamContext->loadBySlug($fallback))) {
        return (string) $fallbackTeam->id();
      }
      return 'all';
    }

    $ids = [$team->id()];
    if ($this->options['include_club'] && ($club = $this->teamContext->getClub())) {
      $ids[] = $club->id();
    }
    return implode('+', $ids);
  }

  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  public function getCacheContexts() {
    return ['team'];
  }

  public function getCacheTags() {
    return ['node_list:team'];
  }

}

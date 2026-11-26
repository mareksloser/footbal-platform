<?php

declare(strict_types=1);

namespace Drupal\club_instagram\Plugin\Block;

use Drupal\club_instagram\PanelBuilder;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Panel s Instagram příspěvky klubu v pěti vzhledech.
 *
 * Totéž umí paragraf „Instagram panel“ (viz PanelBuilder).
 *
 * Použití v šabloně (Twig Tweak):
 * {{ drupal_block('club_instagram_panel', {variant: 'strip', count: 3}) }}
 */
#[Block(
  id: 'club_instagram_panel',
  admin_label: new TranslatableMarkup('Instagram panel'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class InstagramPanelBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private readonly PanelBuilder $panelBuilder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self($configuration, $plugin_id, $plugin_definition, $container->get('club_instagram.panel_builder'));
  }

  public function defaultConfiguration(): array {
    return PanelBuilder::DEFAULTS;
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $c = $this->configuration;
    $form['variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Vzhled'),
      '#options' => $this->panelBuilder->variantOptions(),
      '#default_value' => $c['variant'],
    ];
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nadpis'),
      '#default_value' => $c['heading'],
      '#description' => $this->t('Prázdné = výchozí text vzhledu.'),
    ];
    $form['text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text'),
      '#default_value' => $c['text'],
      '#description' => $this->t('Používá banner a lišta. Prázdné = výchozí text.'),
    ];
    $form['button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text tlačítka'),
      '#default_value' => $c['button'],
    ];
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Počet fotek'),
      '#min' => 0,
      '#max' => 12,
      '#default_value' => $c['count'],
      '#description' => $this->t('0 = podle vzhledu (banner 4, mřížka 4, karta 1, lišta 3, profil bez fotek).'),
    ];
    $form['team'] = [
      '#type' => 'select',
      '#title' => $this->t('Které příspěvky'),
      '#options' => $this->panelBuilder->teamOptions(),
      '#default_value' => $c['team'],
      '#description' => $this->t('U týmu se nejdřív ukážou jeho příspěvky; když jich je málo, doplní se klubové (bez týmu).'),
    ];
    $form['match'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Na detailu zápasu ukázat nejdřív příspěvky k tomuto zápasu'),
      '#default_value' => $c['match'],
    ];
    $form['handle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Jiný účet'),
      '#field_prefix' => '@',
      '#default_value' => $c['handle'],
      '#description' => $this->t('Prázdné = účet klubu z nastavení. Hodí se např. pro partnerský klub.'),
    ];
    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['variant', 'heading', 'text', 'button', 'team'] as $key) {
      $this->configuration[$key] = trim((string) $form_state->getValue($key));
    }
    $this->configuration['count'] = (int) $form_state->getValue('count');
    $this->configuration['match'] = (bool) $form_state->getValue('match');
    $this->configuration['handle'] = ltrim(trim((string) $form_state->getValue('handle')), '@');
  }

  public function build(): array {
    return $this->panelBuilder->build($this->configuration);
  }

}

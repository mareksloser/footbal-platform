<?php

declare(strict_types=1);

namespace Drupal\club_core\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pás partnerů – loga partnerů se zaškrtnutým „Zobrazit v pásu partnerů“.
 */
#[Block(
  id: 'club_partner_strip',
  admin_label: new TranslatableMarkup('Pás partnerů'),
  category: new TranslatableMarkup('Footbal Platform'),
)]
final class PartnerStripBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use LinkSettingTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
    );
  }

  public function defaultConfiguration(): array {
    return [
      'label_display' => FALSE,
      'heading' => 'Podporují nás',
      'cta_label' => 'Staňte se partnerem',
      'cta_url' => '',
      'style' => 'dark',
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $c = $this->configuration;
    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popisek'),
      '#default_value' => $c['heading'],
    ];
    $form['cta_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Odkaz vpravo – text'),
      '#default_value' => $c['cta_label'],
    ];
    $form['cta_url'] = $this->linkElement($this->t('Odkaz vpravo – adresa'), $c['cta_url'], '/partneri');
    $form['style'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pozadí'),
      '#options' => ['dark' => $this->t('Tmavé (k patičce)'), 'light' => $this->t('Světle šedé')],
      '#default_value' => $c['style'],
    ];
    $form['help'] = [
      '#markup' => '<p>' . $this->t('Zobrazí partnery se zaškrtnutým „Zobrazit v pásu partnerů“, v pořadí podle váhy.') . '</p>',
    ];
    return $form;
  }

  public function blockValidate($form, FormStateInterface $form_state): void {
    $this->validateLink($form_state, 'cta_url');
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach (['heading', 'cta_label', 'cta_url', 'style'] as $key) {
      $this->configuration[$key] = trim((string) $form_state->getValue($key));
    }
  }

  public function build(): array {
    $c = $this->configuration;
    $cache = (new CacheableMetadata())->addCacheTags(['taxonomy_term_list:partner']);
    $build = [];
    $partners = $this->partners($cache);
    if ($partners) {
      $build = [
        '#theme' => 'club_partner_strip',
        '#heading' => $c['heading'],
        '#partners' => $partners,
        '#cta_label' => $c['cta_label'],
        '#cta_url' => self::linkUrl($c['cta_url']),
        '#style' => $c['style'] === 'light' ? 'light' : 'dark',
      ];
    }
    $cache->applyTo($build);
    return $build;
  }

  /**
   * Partneři se zaškrtnutým field_partner_footer.
   */
  private function partners(CacheableMetadata $cache): array {
    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    try {
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('vid', 'partner')
        ->condition('status', 1)
        ->condition('field_partner_footer', 1)
        ->sort('weight')
        ->sort('name')
        ->execute();
    }
    catch (\Exception) {
      // Pole ještě neexistuje (drush updb neproběhl) nebo chybí slovník.
      return [];
    }

    $partners = [];
    foreach ($storage->loadMultiple($ids) as $term) {
      $cache->addCacheableDependency($term);
      $url = NULL;
      if ($term->hasField('field_link') && !$term->get('field_link')->isEmpty()) {
        $generated = $term->get('field_link')->first()->getUrl()->toString(TRUE);
        $cache->addCacheableDependency($generated);
        $url = $generated->getGeneratedUrl();
      }
      $partners[] = [
        'name' => $term->label(),
        'url' => $url,
        'logo_url' => $this->logoUrl($term, $cache),
      ];
    }
    return $partners;
  }

  /**
   * URL loga partnera (pole field_logo – média) přes styl club_logo.
   */
  private function logoUrl(FieldableEntityInterface $term, CacheableMetadata $cache): ?string {
    if (!$term->hasField('field_logo')) {
      return NULL;
    }
    $items = $term->get('field_logo');
    $media = $items instanceof EntityReferenceFieldItemListInterface ? ($items->referencedEntities()[0] ?? NULL) : NULL;
    if (!$media instanceof MediaInterface) {
      return NULL;
    }
    $cache->addCacheableDependency($media);
    $field = $media->getSource()->getConfiguration()['source_field'] ?? NULL;
    $file = $field && $media->hasField($field) ? ($media->get($field)->entity ?? NULL) : NULL;
    if (!$file || !method_exists($file, 'getFileUri')) {
      return NULL;
    }
    $uri = $file->getFileUri();
    $style = $this->entityTypeManager->getStorage('image_style')->load('club_logo');
    return $style
      ? $this->fileUrlGenerator->transformRelative($style->buildUrl($uri))
      : $this->fileUrlGenerator->generateString($uri);
  }

}

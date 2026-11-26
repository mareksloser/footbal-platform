<?php

declare(strict_types=1);

namespace Drupal\club_instagram;

use Drupal\club_core\TeamContext;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Sestaví Instagram panel – společné pro blok i paragraf.
 */
final class PanelBuilder {

  use StringTranslationTrait;

  /**
   * Vzhledy: popisek, výchozí nadpis/text/tlačítko, výchozí počet fotek, styl obrázku.
   */
  public const VARIANTS = [
    'banner' => ['Promo banner', 'Sledujte dění v klubu ze zákulisí', 'Fotky, videa a emoce z kabiny přímo ve vašem telefonu.', 'Sledovat', 4, 'square'],
    'grid' => ['Mřížka příspěvků', 'Jsme na Instagramu', NULL, 'Sledovat profil', 4, 'square'],
    'card' => ['Kompaktní karta', 'Buďte s námi i mimo hřiště!', NULL, 'Sledovat na Instagramu', 1, 'portrait'],
    'profile' => ['Karta profilu (např. sekce Partneři)', 'Oficiální Instagram klubu', NULL, 'Sledovat', 0, 'square'],
    'strip' => ['Spodní lišta', 'Nenechte si ujít žádný gól ani novinku!', 'Sledujte nás', 'Sledovat', 3, 'square'],
  ];

  /**
   * Výchozí nastavení panelu.
   */
  public const DEFAULTS = [
    'variant' => 'grid',
    'heading' => '',
    'text' => '',
    'button' => '',
    // 0 = výchozí počet podle vzhledu.
    'count' => 0,
    // all | context (tým podle stránky / přepínače) | ID uzlu týmu.
    'team' => 'all',
    'match' => TRUE,
    'handle' => '',
  ];

  /**
   * Mapování polí paragrafu „Instagram panel“ na klíče nastavení.
   */
  private const PARAGRAPH_FIELDS = [
    'variant' => 'field_igp_variant',
    'heading' => 'field_igp_heading',
    'text' => 'field_igp_text',
    'button' => 'field_igp_button',
    'count' => 'field_igp_count',
    'team' => 'field_igp_team',
    'match' => 'field_igp_match',
    'handle' => 'field_igp_handle',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly RouteMatchInterface $routeMatch,
    private readonly TeamContext $teamContext,
  ) {}

  /**
   * Volby pro výběr vzhledu.
   */
  public function variantOptions(): array {
    return array_map(fn(array $v) => (string) $this->t($v[0]), self::VARIANTS);
  }

  /**
   * Volby pro výběr příspěvků (všechny / aktuální tým / konkrétní tým).
   */
  public function teamOptions(): array {
    $options = [
      'all' => (string) $this->t('Všechny příspěvky'),
      'context' => (string) $this->t('Aktuální tým (podle stránky / přepínače týmů)'),
    ];
    foreach ($this->teamContext->getTeams() as $team) {
      $options[(string) $team->id()] = (string) $this->t('Jen tým: @team', ['@team' => $team->label()]);
    }
    return $options;
  }

  /**
   * Allowed values callback pro pole field_igp_variant.
   */
  public static function variantAllowedValues(FieldStorageDefinitionInterface $definition, $entity = NULL, &$cacheable = TRUE): array {
    return \Drupal::service('club_instagram.panel_builder')->variantOptions();
  }

  /**
   * Allowed values callback pro pole field_igp_team.
   */
  public static function teamAllowedValues(FieldStorageDefinitionInterface $definition, $entity = NULL, &$cacheable = TRUE): array {
    // Seznam týmů se mění, nesmí se cachovat s definicí pole.
    $cacheable = FALSE;
    return \Drupal::service('club_instagram.panel_builder')->teamOptions();
  }

  /**
   * Render array panelu z paragrafu typu instagram_panel.
   */
  public function buildFromParagraph(ParagraphInterface $paragraph): array {
    $settings = [];
    foreach (self::PARAGRAPH_FIELDS as $key => $field) {
      if ($paragraph->hasField($field) && !$paragraph->get($field)->isEmpty()) {
        $settings[$key] = $paragraph->get($field)->value;
      }
    }
    $build = $this->build($settings);
    CacheableMetadata::createFromRenderArray($build)->addCacheableDependency($paragraph)->applyTo($build);
    return $build;
  }

  /**
   * Render array panelu podle nastavení (klíče viz DEFAULTS).
   */
  public function build(array $settings): array {
    $c = $settings + self::DEFAULTS;
    $variant = isset(self::VARIANTS[$c['variant']]) ? $c['variant'] : 'grid';
    [, $heading, $text, $button, $default_count, $style] = self::VARIANTS[$variant];
    $count = (int) $c['count'] ?: $default_count;
    $own_handle = ltrim(trim((string) $c['handle']), '@');

    $cache = new CacheableMetadata();
    $config = $this->configFactory->get('club_instagram.settings');
    $cache->addCacheableDependency($config)->addCacheTags(['config:system.theme']);
    $handle = $own_handle ?: (string) $config->get('handle');
    $profile_url = $own_handle || !$config->get('profile_url')
      ? ($handle ? 'https://www.instagram.com/' . rawurlencode($handle) . '/' : NULL)
      : (string) $config->get('profile_url');

    $items = $count > 0 ? $this->loadItems($count, (string) $c['team'], (bool) $c['match'], $style, $profile_url, $cache) : [];

    $build = [
      '#theme' => 'club_instagram_panel',
      '#variant' => $variant,
      '#heading' => $c['heading'] ?: $heading,
      '#text' => $c['text'] ?: $text,
      '#button' => $c['button'] ?: $button,
      '#handle' => $handle,
      '#profile_url' => $profile_url,
      '#logo_url' => $variant === 'profile' ? theme_get_setting('logo.url') : NULL,
      '#items' => $items,
      '#attached' => ['library' => ['club_instagram/panel']],
    ];
    $cache->applyTo($build);
    return $build;
  }

  /**
   * Příspěvky v pořadí: k zápasu → k týmu → klubové (bez týmu).
   */
  private function loadItems(int $count, string $team_setting, bool $match, string $style, ?string $fallback_url, CacheableMetadata $cache): array {
    $cache->addCacheTags(['node_list:instagram_post']);
    $fields = $this->entityFieldManager->getFieldDefinitions('node', 'instagram_post');
    $storage = $this->entityTypeManager->getStorage('node');

    $groups = [];
    if ($match && isset($fields['field_ig_match'])) {
      $cache->addCacheContexts(['route']);
      $node = $this->routeMatch->getParameter('node');
      if ($node instanceof NodeInterface && $node->bundle() === 'match') {
        $groups[] = ['field_ig_match', $node->id()];
      }
    }
    $team = NULL;
    if ($team_setting === 'context') {
      $cache->addCacheContexts(['team']);
      $team = $this->teamContext->getTeam();
    }
    elseif ($team_setting !== 'all' && $team_setting !== '') {
      $team = $storage->load($team_setting);
    }
    if ($team instanceof NodeInterface && isset($fields['field_team']) && !$this->teamContext->isClub($team)) {
      $groups[] = ['field_team', $team->id()];
      $groups[] = ['field_team', NULL];
    }
    else {
      $groups[] = [NULL, NULL];
    }

    $ids = [];
    foreach ($groups as [$field, $value]) {
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'instagram_post')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, $count);
      if ($field && $value === NULL) {
        $query->notExists($field);
      }
      elseif ($field) {
        $query->condition($field, $value);
      }
      if ($ids) {
        $query->condition('nid', $ids, 'NOT IN');
      }
      $ids = array_merge($ids, array_values($query->execute()));
      if (count($ids) >= $count) {
        break;
      }
    }

    $image_style = $this->entityTypeManager->getStorage('image_style')->load('club_instagram_' . $style);
    $items = [];
    foreach ($storage->loadMultiple(array_slice($ids, 0, $count)) as $post) {
      $cache->addCacheableDependency($post);
      $image = $post->get('field_ig_image')->first();
      $file = $image?->entity;
      if (!$file) {
        continue;
      }
      $uri = $file->getFileUri();
      $caption = trim((string) ($post->get('field_ig_caption')->value ?? ''));
      $items[] = [
        'src' => $image_style
          ? $this->fileUrlGenerator->transformRelative($image_style->buildUrl($uri))
          : $this->fileUrlGenerator->generateString($uri),
        'alt' => $image->alt ?: $post->label(),
        'caption' => $caption ?: $post->label(),
        'url' => $post->get('field_ig_link')->uri ?? $fallback_url,
      ];
    }
    return $items;
  }

}

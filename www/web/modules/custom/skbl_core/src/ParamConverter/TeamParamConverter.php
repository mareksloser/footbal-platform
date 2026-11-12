<?php

declare(strict_types=1);

namespace Drupal\skbl_core\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\NodeInterface;
use Drupal\skbl_core\TeamContext;
use Symfony\Component\Routing\Route;

/**
 * Převede {node} v cestě na tým – podle zkratky (u15) nebo nid.
 *
 * Neexistující tým vrací NULL, takže Drupal odpoví 404.
 */
final class TeamParamConverter implements ParamConverterInterface {

  public function __construct(
    private readonly TeamContext $teamContext,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function convert($value, $definition, $name, array $defaults) {
    $value = (string) $value;
    $team = ctype_digit($value)
      ? $this->entityTypeManager->getStorage('node')->load($value)
      : $this->teamContext->loadBySlug($value);
    return $team instanceof NodeInterface && $team->bundle() === TeamContext::BUNDLE ? $team : NULL;
  }

  public function applies($definition, $name, Route $route) {
    return ($definition['type'] ?? NULL) === 'skbl_team';
  }

}

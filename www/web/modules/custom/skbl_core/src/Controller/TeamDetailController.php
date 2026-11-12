<?php

declare(strict_types=1);

namespace Drupal\skbl_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\skbl_core\TeamContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stránka „O týmu“ – /tym/u15/o-tymu.
 *
 * Vykreslí node týmu v režimu zobrazení „detail“ (vlastní Layout Builder).
 * Route parametr se jmenuje {node}, takže TeamContext tým pozná stejně
 * jako na stránce nodu a všechny views bloky se filtrují samy.
 */
final class TeamDetailController extends ControllerBase {

  public const VIEW_MODE = 'detail';

  private TeamContext $teamContext;

  public static function create(ContainerInterface $container) {
    $instance = new self();
    $instance->teamContext = $container->get('skbl_core.team_context');
    return $instance;
  }

  public function view(NodeInterface $node) {
    // „Celý klub“ nemá vlastní soupisku – přehled týmů, nebo úvod.
    if ($this->teamContext->isClub($node)) {
      $teams = \Drupal::pathValidator()->getUrlIfValid('/tymy');
      return $teams
        ? $this->redirect($teams->getRouteName(), $teams->getRouteParameters())
        : $this->redirect('<front>');
    }
    return $this->entityTypeManager()->getViewBuilder('node')->view($node, self::VIEW_MODE);
  }

  public function title(NodeInterface $node): string {
    return (string) $node->label();
  }

}

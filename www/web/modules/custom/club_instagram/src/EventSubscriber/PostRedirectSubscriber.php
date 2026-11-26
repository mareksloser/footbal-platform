<?php

declare(strict_types=1);

namespace Drupal\club_instagram\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Instagram příspěvek nemá vlastní stránku – návštěvníka přesměruje na Instagram.
 *
 * Kdo může příspěvek upravit, uvidí normální detail (kvůli kontrole obsahu).
 */
final class PostRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly AccountInterface $currentUser,
  ) {}

  public static function getSubscribedEvents(): array {
    // Až po routingu (32), aby byl k dispozici route match.
    return [KernelEvents::REQUEST => ['onRequest', 28]];
  }

  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest() || $this->routeMatch->getRouteName() !== 'entity.node.canonical') {
      return;
    }
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface || $node->bundle() !== 'instagram_post' || $node->access('update', $this->currentUser)) {
      return;
    }
    $url = $node->get('field_ig_link')->uri ?? NULL;
    if (!$url) {
      $config = \Drupal::config('club_instagram.settings');
      $url = $config->get('profile_url') ?: ($config->get('handle') ? 'https://www.instagram.com/' . $config->get('handle') . '/' : '/');
    }
    $response = new TrustedRedirectResponse($url, 301);
    $response->getCacheableMetadata()->addCacheableDependency($node)->addCacheContexts(['user.permissions']);
    $event->setResponse($response);
  }

}

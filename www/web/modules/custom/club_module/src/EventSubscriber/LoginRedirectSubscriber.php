<?php

declare(strict_types=1);

namespace Drupal\club_module\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LoginRedirectSubscriber implements EventSubscriberInterface {

  // Machine name klienta v Drupalu.
  private const CLIENT_ID = 'club';

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly AccountProxyInterface $currentUser,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly OpenIDConnectClaims $claims,
    private readonly OpenIDConnectSession $session,
  ) {}

  public static function getSubscribedEvents(): array {
    // Až po routeru (priorita 32), aby byla známá route.
    return [KernelEvents::REQUEST => ['onRequest', 28]];
  }

  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()
      || $this->routeMatch->getRouteName() !== 'user.login'
      || $this->currentUser->isAuthenticated()
      // Záchranná brzda: /user/login?showcore ukáže normální formulář.
      || $event->getRequest()->query->has('showcore')) {
      return;
    }

    $client = $this->entityTypeManager
      ->getStorage('openid_connect_client')
      ->load(self::CLIENT_ID);
    if (!$client || !$client->status()) {
      return;
    }

    $this->session->saveDestination();
    $this->session->saveOp('login');
    $plugin = $client->getPlugin();
    $event->setResponse($plugin->authorize($this->claims->getScopes($plugin)));
  }

}
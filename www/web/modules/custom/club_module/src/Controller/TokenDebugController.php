<?php

declare(strict_types=1);

namespace Drupal\club_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shows the decoded tokens received from Team Auth at the last login.
 */
final class TokenDebugController extends ControllerBase {

  private const TIME_CLAIMS = ['iat', 'nbf', 'exp', 'auth_time'];

  public function view(Request $request): array {
    $build = ['#cache' => ['max-age' => 0]];

    $data = $request->getSession()->get('club_module.token_debug');
    if (!$data) {
      $build['empty'] = [
        '#markup' => $this->t('No tokens stored in this session. Log out and log in again through Team Auth.'),
      ];
      return $build;
    }

    $build['meta'] = [
      '#markup' => $this->t('Login: @time, client plugin: @plugin', [
        '@time' => date('Y-m-d H:i:s', $data['time']),
        '@plugin' => $data['plugin_id'] ?? '-',
      ]),
    ];

    foreach (['id_token' => 'ID token', 'access_token' => 'Access token'] as $key => $label) {
      if (empty($data['tokens'][$key])) {
        continue;
      }
      $jwt = self::decodeJwt($data['tokens'][$key]);
      $build[$key] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => TRUE,
      ];
      if ($jwt === NULL) {
        // Opaque (non-JWT) token, show it as is.
        $build[$key]['raw'] = self::pre($data['tokens'][$key]);
        continue;
      }
      $build[$key]['times'] = self::times($jwt['payload'] ?? []);
      $build[$key]['header'] = self::pre(self::json($jwt['header']), 'Header');
      $build[$key]['payload'] = self::pre(self::json($jwt['payload']), 'Payload');
    }

    if (!empty($data['userinfo'])) {
      $build['userinfo'] = [
        '#type' => 'details',
        '#title' => 'Userinfo (after hook_openid_connect_userinfo_alter)',
        '#open' => TRUE,
        'json' => self::pre(self::json($data['userinfo'])),
      ];
    }

    return $build;
  }

  /**
   * Decodes a JWT without verifying it (debug only).
   */
  private static function decodeJwt(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
      return NULL;
    }
    $decode = static fn (string $part): mixed => json_decode(base64_decode(strtr($part, '-_', '+/')), TRUE);

    return ['header' => $decode($parts[0]), 'payload' => $decode($parts[1])];
  }

  /**
   * Lists time claims in a readable form and marks expired tokens.
   */
  private static function times(array $payload): array {
    $items = [];
    foreach (self::TIME_CLAIMS as $claim) {
      if (isset($payload[$claim]) && is_numeric($payload[$claim])) {
        $items[] = sprintf('%s: %s', $claim, date('Y-m-d H:i:s', (int) $payload[$claim]));
      }
    }
    if (isset($payload['exp']) && $payload['exp'] < time()) {
      $items[] = 'EXPIRED';
    }

    return ['#theme' => 'item_list', '#items' => $items];
  }

  private static function json(mixed $data): string {
    return (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  }

  /**
   * Renders escaped text in a <pre> block.
   */
  private static function pre(string $text, ?string $title = NULL): array {
    return [
      '#type' => 'inline_template',
      '#template' => '{% if title %}<h4>{{ title }}</h4>{% endif %}<pre style="white-space: pre-wrap; word-break: break-all;">{{ text }}</pre>',
      '#context' => ['title' => $title, 'text' => $text],
    ];
  }

}
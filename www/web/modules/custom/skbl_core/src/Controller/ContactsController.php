<?php

declare(strict_types=1);

namespace Drupal\skbl_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\skbl_core\ContactsBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Stránka /kontakty.
 */
final class ContactsController extends ControllerBase {

  private ContactsBuilder $builder;

  public static function create(ContainerInterface $container) {
    $instance = new self();
    $instance->builder = $container->get('skbl_core.contacts_builder');
    return $instance;
  }

  public function page(): array {
    return $this->builder->build();
  }

}

<?php

declare(strict_types=1);

namespace Drupal\club_instagram\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Účet klubu na Instagramu (Konfigurace › Webové služby › Instagram klubu).
 */
final class SettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'club_instagram_settings';
  }

  protected function getEditableConfigNames(): array {
    return ['club_instagram.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('club_instagram.settings');
    $form['handle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Účet na Instagramu'),
      '#field_prefix' => '@',
      '#description' => $this->t('Bez zavináče, např. skvinarice.'),
      '#default_value' => $config->get('handle'),
      '#required' => TRUE,
    ];
    $form['profile_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Odkaz na profil'),
      '#description' => $this->t('Prázdné = https://www.instagram.com/<účet>/'),
      '#default_value' => $config->get('profile_url'),
    ];
    $form['posts'] = [
      '#type' => 'item',
      '#title' => $this->t('Příspěvky'),
      '#markup' => $this->t('Fotky do panelů přidáte jako obsah typu <a href=":add">Instagram příspěvek</a>, přehled najdete v <a href=":list">Obsahu</a>. Panely vložíte jako blok „Instagram panel“.', [
        ':add' => Url::fromRoute('node.add', ['node_type' => 'instagram_post'])->toString(),
        ':list' => Url::fromRoute('system.admin_content', [], ['query' => ['type' => 'instagram_post']])->toString(),
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('club_instagram.settings')
      ->set('handle', ltrim(trim((string) $form_state->getValue('handle')), '@'))
      ->set('profile_url', trim((string) $form_state->getValue('profile_url')))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

<?php

namespace Drupal\lightning_media;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

class EmbedCodePreviewHandler extends PreviewHandlerBase {

  use DependencySerializationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationInterface $translator) {
    parent::__construct($entity_manager, $translator);
    $this->viewBuilder = $entity_manager->getViewBuilder('media');
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    parent::alterForm($form, $form_state);

    $entity = $this->getEntity($form_state);
    $field = $this->getField($entity)->getName();

    $form[$field]['widget'][0]['value']['#ajax'] = [
      'event' => 'change',
      'callback' => [$this, 'getPreviewContent'],
    ];
    $form['preview'] = [
      '#type' => 'container',
      'entity' => [
        '#markup' => '',
      ],
    ];

    $key = [$field, 0, 'value'];
    if ($form_state->hasValue($key)) {
      $entity->set($field, $form_state->getValue($key));
    }
    if ($entity->get($field)->value) {
      $form['preview']['entity'] = $this->viewBuilder->view($entity);
    }
  }

  public static function getPreviewContent(array &$form) {
    $response = new AjaxResponse();

    $command = new HtmlCommand('#edit-preview', $form['preview']['entity']);
    $response->addCommand($command);

    return static::toggleMetaData($response);
  }

}

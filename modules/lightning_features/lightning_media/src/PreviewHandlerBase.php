<?php

namespace Drupal\lightning_media;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media_entity\MediaInterface;

/**
 * Base class for preview handlers.
 */
abstract class PreviewHandlerBase implements PreviewHandlerInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The media bundle storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleStorage;

  /**
   * The storage handler for configurable fields.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorage;

  /**
   * The storage handler for entity form displays.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $displayStorage;

  /**
   * PreviewHandlerBase constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface|NULL $translator
   *   (optional) The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, TranslationInterface $translator = NULL) {
    $this->bundleStorage = $entity_manager->getStorage('media_bundle');
    $this->fieldStorage = $entity_manager->getStorage('field_config');
    $this->displayStorage = $entity_manager->getStorage('entity_form_display');
    $this->stringTranslation = $translator;
  }

  /**
   * {@inheritdoc}
   */
  public function extraFields($bundle) {
    if (is_string($bundle)) {
      $bundle = $this->bundleStorage->load($bundle);
    }
    $extra = array();
    $extra['media'][$bundle->id()]['form']['preview'] = [
      'label' => $this->t('Preview'),
      'description' => $this->t('A live preview of the @bundle.', [
        '@bundle' => $bundle->label(),
      ]),
    ];

    return $extra;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->getEntity($form_state);

    $display = $this->getDisplay($entity);
    if ($display->getThirdPartySetting('field_group', 'group_metadata')) {
      $form['#pre_render'][] = [$this, 'prepareMetaData'];
    }
  }

  /**
   * Pre-render callback. Alters the group_metadata group.
   *
   * @param array $form
   *   The form's render element.
   *
   * @return array
   *   The modified form element.
   */
  public function prepareMetaData(array $form) {
    $form['group_metadata']['#attributes']['class'][] = 'metadata';
    $form['group_metadata']['#attributes']['style'] = ['display: none;'];
    return $form;
  }

  /**
   * AJAX callback helper. Adds a command to show or hide the metadata group.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The outgoing AJAX response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The modified AJAX response.
   */
  protected static function toggleMetaData(AjaxResponse $response) {
    $command = new InvokeCommand('.metadata', 'toggle', [600]);
    return $response->addCommand($command);
  }

  /**
   * Returns the media entity being manipulated by the form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The form's media entity.
   */
  protected function getEntity(FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form */
    $form = $form_state->getFormObject();
    return $form->getEntity();
  }

  /**
   * Returns the display configuration for the form.
   *
   * @param \Drupal\media_entity\MediaInterface $entity
   *   The media entity whose form is being displayed.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form's display configuration.
   */
  protected function getDisplay(MediaInterface $entity) {
    $id = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.default';
    return $this->displayStorage->load($id);
  }

  /**
   * Returns the source field entity for a media entity.
   *
   * @param \Drupal\media_entity\MediaInterface $entity
   *   The media entity.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The source field's config entity.
   */
  protected function getField(MediaInterface $entity) {
    $type_config = $entity->getType()->getConfiguration();
    $id = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $type_config['source_field'];

    return $this->fieldStorage->load($id);
  }

}

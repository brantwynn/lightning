<?php

/**
 * @file
 * Contains \Drupal\lightning_media\MediaFormPreview.
 */

namespace Drupal\lightning_media;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\media_entity\MediaInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles the generation of live previews in media entity forms.
 */
class MediaFormPreview {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity_form_display storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $displayStorage;

  /**
   * The field_config storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorage;

  /**
   * The media_bundle storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleStorage;

  /**
   * The media view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * MediaFormPreview constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translator
   *   (optional) The translation service.
   */
  public function __construct(EntityTypeManagerInterface $manager, TranslationInterface $translator = NULL) {
    $this->displayStorage = $manager->getStorage('entity_form_display');
    $this->fieldStorage = $manager->getStorage('field_config');
    $this->bundleStorage = $manager->getStorage('media_bundle');
    $this->viewBuilder = $manager->getViewBuilder('media');
    $this->stringTranslation = $translator;
  }

  /**
   * Defines an extra preview field on a media bundle.
   *
   * @param string|\Drupal\media_entity\MediaBundleInterface $bundle
   *   The bundle, or its ID.
   *
   * @return array
   *   The extra field definitions.
   */
  public function addToBundle($bundle) {
    if (is_string($bundle)) {
      $bundle = $this->bundleStorage->load($bundle);
    }

    $extra = [];
    $extra['media'][$bundle->id()]['form']['preview'] = [
      'label' => $this->t('Preview'),
      'description' => $this->t('A live preview of the @bundle.', [
        '@bundle' => $bundle->label(),
      ]),
      // @TODO: Put this directly after the source field.
      'weight' => 1,
    ];
    return $extra;
  }

  /**
   * Alters a media entity form to add preview functionality if needed.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    // Get the entity from the form object.
    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\media_entity\MediaInterface $entity */
    $entity = $form_object->getEntity();

    if ($this->isExternalPreview($entity)) {
      $this->externalPreview($form, $form_state, $entity);
    }
    elseif ($this->isInternalPreview($entity)) {
      $this->internalPreview($form, $form_state, $entity);
    }

    $group = $this->getDisplayConfiguration($entity)->getThirdPartySetting('field_group', 'group_metadata');
    if ($group) {
      $form['#pre_render'][] = [$this, 'prepareMetaData'];
    }
  }

  public function prepareMetaData(array $element) {
    $element['group_metadata']['#attributes']['class'][] = 'metadata';
    $element['group_metadata']['#attributes']['style'] = ['display: none;'];
    return $element;
  }

  protected function isInternalPreview(MediaInterface $entity) {
    return in_array($this->getSourceField($entity)->getType(), [
      'file',
      'image',
    ]);
  }

  protected function isExternalPreview(MediaInterface $entity) {
    $component = $this->getDisplayConfiguration($entity)->getComponent('preview');
    $field_type = $this->getSourceField($entity)->getType();

    return $component && in_array($field_type, [
      'string_long',
      'video_embed_field',
    ]);
  }

  protected function internalPreview(array &$form, FormStateInterface $form_state, MediaInterface $entity) {
    $field = $this->getSourceField($entity)->getName();
    $form[$field]['widget'][0]['#process'][] = [$this, 'setInternalPreviewAjaxCallback'];
  }

  public function setInternalPreviewAjaxCallback(array $element) {
    $callback = [$this, 'uploadAjaxCallback'];
    $element['upload_button']['#ajax']['callback'] = $callback;
    $element['remove_button']['#ajax']['callback'] = $callback;
    return $element;
  }

  protected function externalPreview(array &$form, FormStateInterface $form_state, MediaInterface $entity) {
    $field = $this->getSourceField($entity)->getName();

    $form[$field]['widget'][0]['value']['#ajax'] = [
      'event' => 'change',
      'callback' => [$this, 'getPreviewContent'],
    ];
    $form['preview']['#type'] = 'container';

    $key = [$field, 0, 'value'];
    if ($form_state->hasValue($key)) {
      $entity->set($field, $form_state->getValue($key));
    }
    if ($entity->get($field)->value) {
      $form['preview']['entity'] = $this->viewBuilder->view($entity);
    }
  }

  /**
   * @return \Drupal\Core\Field\FieldConfigInterface
   */
  protected function getSourceField(MediaInterface $entity) {
    $type_config = $entity->getType()->getConfiguration();
    $source_field = $type_config['source_field'];
    $field = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $source_field;

    return $this->fieldStorage->load($field);
  }

  /**
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected function getDisplayConfiguration(EntityInterface $entity) {
    $id = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.default';
    return $this->displayStorage->load($id);
  }

  public function uploadAjaxCallback(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return $this->toggleMetaData($response);
  }

  /**
   * AJAX callback. Returns the content of the live preview, if any.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The renderable live preview.
   */
  public function getPreviewContent(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $content = $form['preview']['entity'] ?: ['#markup' => ''];
    $command = new HtmlCommand('#edit-preview', $content);
    $response->addCommand($command);

    return $this->toggleMetaData($response);
  }

  protected function toggleMetaData(AjaxResponse $response) {
    $command = new InvokeCommand('.metadata', 'toggle', [600]);
    return $response->addCommand($command);
  }

}

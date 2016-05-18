<?php

/**
 * @file
 * Contains \Drupal\lightning_media\MediaFormPreview.
 */

namespace Drupal\lightning_media;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Handles the generation of live previews in media entity forms.
 */
class MediaFormPreview {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The ID of the extra preview field.
   */
  const PREVIEW_FIELD = 'preview';

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
    $extra['media'][$bundle->id()]['form'][static::PREVIEW_FIELD] = [
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

    // Load the form display configuration.
    $display = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.default';
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = $this->displayStorage->load($display);

    $component = $display->getComponent(static::PREVIEW_FIELD);
    if ($component) {
      $form[static::PREVIEW_FIELD]['#type'] = 'container';

      $type_config = $entity->getType()->getConfiguration();
      $source_field = $type_config['source_field'];
      $field = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.' . $source_field;
      /** @var \Drupal\Core\Field\FieldConfigInterface $field */
      $field = $this->fieldStorage->load($field);

      switch ($field->getType()) {
        case 'video_embed_field':
        case 'string_long':
          $form[$source_field]['widget'][0]['value']['#ajax'] = [
            'event' => 'change',
            'method' => 'html',
            'wrapper' => 'edit-' . str_replace('_', '-', static::PREVIEW_FIELD),
            'callback' => [$this, 'getPreviewContent'],
          ];
          break;

        default:
          break;
      }

      $key = [$source_field, 0, 'value'];
      if ($form_state->hasValue($key)) {
        $entity->set($source_field, $form_state->getValue($key));
      }
      if ($entity->get($source_field)->value) {
        $form[static::PREVIEW_FIELD]['entity'] = $this->viewBuilder->view($entity);
      }
    }
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
    return $form[static::PREVIEW_FIELD]['entity'] ?: ['#markup' => ''];
  }

}

<?php

namespace Drupal\lightning_media;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\media_entity\MediaInterface;

abstract class PreviewHandlerBase implements PreviewHandlerInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $displayStorage;

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

  public function prepareMetaData(array $form) {
    $form['group_metadata']['#attributes']['class'][] = 'metadata';
    $form['group_metadata']['#attributes']['style'] = ['display: none;'];
    return $form;
  }

  protected static function toggleMetaData(AjaxResponse $response) {
    $command = new InvokeCommand('.metadata', 'toggle', [600]);
    return $response->addCommand($command);
  }

  /**
   * @return \Drupal\media_entity\MediaInterface
   */
  protected function getEntity(FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityFormInterface $form */
    $form = $form_state->getFormObject();
    return $form->getEntity();
  }

  /**
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected function getDisplay(MediaInterface $entity) {
    $id = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.default';
    return $this->displayStorage->load($id);
  }

  /**
   * @return \Drupal\Core\Field\FieldConfigInterface
   */
  protected function getField(MediaInterface $entity) {
    $type_config = $entity->getType()->getConfiguration();
    $id = $entity->getEntityTypeId() . '.' . $entity->bundle() . '.'.  $type_config['source_field'];

    return $this->fieldStorage->load($id);
  }

}

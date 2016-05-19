<?php

namespace Drupal\lightning_media;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

class FieldProxy {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(EntityFieldManagerInterface $field_manager, EntityFormBuilderInterface $form_builder) {
    $this->fieldManager = $field_manager;
    $this->formBuilder = $form_builder;
  }

  public function getProxyFields(EntityInterface $entity, array $exclude = []) {
    $exclude = array_combine($exclude, $exclude);

    $fields = array();
    $fields = array_merge($fields, $this->getRequiredFields($entity));
    $fields = array_merge($fields, $this->getExtraFields($entity));
    $fields = array_diff_key($fields, $exclude);

    $form = $this->formBuilder->getForm($entity);

    return array_intersect_key($form, $fields);
  }

  protected function getRequiredFields(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $fields = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);

    return array_filter($fields, function (FieldDefinitionInterface $field) {
      return $field->isRequired();
    });
  }

  protected function getExtraFields(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $fields = $this->fieldManager->getExtraFields($entity_type, $bundle);

    return array_filter($fields['form'], function (array $field) {
      return isset($field['visible']) ? $field['visible'] : TRUE;
    });
  }

}

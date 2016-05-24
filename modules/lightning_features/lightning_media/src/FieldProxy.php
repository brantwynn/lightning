<?php

namespace Drupal\lightning_media;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * A service for extracting form structures for certain entity fields.
 */
class FieldProxy {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $formBuilder;

  /**
   * FieldProxy constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder
   *   The entity form builder.
   */
  public function __construct(EntityFieldManagerInterface $field_manager, EntityFormBuilderInterface $form_builder) {
    $this->fieldManager = $field_manager;
    $this->formBuilder = $form_builder;
  }

  /**
   * Returns the form structure for proxied fields of a single entity.
   *
   * The proxied fields will include all of the entity's required fields, both
   * base and configurable. Visible extra fields will also be included.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string[] $exclude
   *   (optional) The names of fields to be excluded from the output.
   *
   * @return array
   *   A Form API structure containing the proxied fields.
   */
  public function getProxyFields(EntityInterface $entity, array $exclude = []) {
    $exclude = array_combine($exclude, $exclude);

    $fields = array();
    $fields = array_merge($fields, $this->getRequiredFields($entity));
    $fields = array_merge($fields, $this->getExtraFields($entity));
    $fields = array_diff_key($fields, $exclude);

    $form = $this->formBuilder->getForm($entity);

    return array_intersect_key($form, $fields);
  }

  /**
   * Returns the definitions of required fields to be proxied.
   *
   * Both base and configurable fields are included in the returned array, but
   * optional fields are never included.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The required field definitions.
   */
  protected function getRequiredFields(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $fields = $this->fieldManager->getFieldDefinitions($entity_type, $bundle);

    return array_filter($fields, function (FieldDefinitionInterface $field) {
      return $field->isRequired();
    });
  }

  /**
   * Returns definitions of extra fields to be proxied.
   *
   * Extra fields are defined by hook_entity_extra_field_info().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array[]
   *   The extra field definitions.
   */
  protected function getExtraFields(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $fields = $this->fieldManager->getExtraFields($entity_type, $bundle);

    return array_filter($fields['form'], function (array $field) {
      return isset($field['visible']) ? $field['visible'] : TRUE;
    });
  }

}

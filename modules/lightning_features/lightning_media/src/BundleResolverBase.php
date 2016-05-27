<?php

namespace Drupal\lightning_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\media_entity\MediaBundleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BundleResolverBase extends PluginBase implements BundleResolverInterface, ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fieldStorage;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->bundleStorage = $entity_type_manager->getStorage('media_bundle');
    $this->fieldStorage = $entity_type_manager->getStorage('field_config');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * @return MediaBundleInterface[]
   */
  protected function getPossibleBundles() {
    $plugin_definition = $this->getPluginDefinition();

    $filter = function (MediaBundleInterface $bundle) use ($plugin_definition) {
      $field = $this->getSourceField($bundle);
      return $field ? in_array($field->getType(), $plugin_definition['field_types']) : FALSE;
    };

    return array_filter($this->bundleStorage->loadMultiple(), $filter);
  }

  /**
   * @return \Drupal\Core\Field\FieldConfigInterface
   */
  protected function getSourceField(MediaBundleInterface $bundle) {
    $type_config = $bundle->getType()->getConfiguration();
    $id = 'media.' . $bundle->id() . '.' . $type_config['source_field'];
    return $this->fieldStorage->load($id);
  }

}

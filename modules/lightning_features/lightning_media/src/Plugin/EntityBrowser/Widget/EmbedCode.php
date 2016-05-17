<?php

/**
 * @file
 * Contains \Drupal\lightning_media\Plugin\EntityBrowser\Widget\EmbedCode.
 */

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\lightning_media\MediaBundleResolver;
use Drupal\lightning_media\MediaFormPreview;
use Drupal\media_entity\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * An Entity Browser widget allowing the creation of different types of media
 * entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Allows creation of media entities from embed codes.")
 * )
 */
class EmbedCode extends WidgetBase {

  /**
   * @var \Drupal\lightning_media\MediaBundleResolver
   */
  protected $bundleResolver;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, MediaBundleResolver $bundle_resolver, AccountInterface $current_user, EntityFormBuilderInterface $entity_form_builder, EntityFieldManagerInterface $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->bundleResolver = $bundle_resolver;
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity.manager'),
      $container->get('lightning.media.bundle_resolver'),
      $container->get('current_user'),
      $container->get('entity.form_builder'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = array();

    $form['embed_code'] = array(
      '#type' => 'textarea',
      '#placeholder' => $this->t('Enter a URL or embed code...'),
      '#ajax' => array(
        'event' => 'change',
        'method' => 'html',
        'wrapper' => 'edit-proxied',
        'callback' => [$this, 'getProxiedFields'],
      ),
    );
    $form['proxied']['#type'] = 'container';

    if ($embed_code = $form_state->getValue('embed_code')) {
      $entity = $this->generateEntity($embed_code);
      if ($entity) {
        $form['proxied'] = array_intersect_key($this->entityFormBuilder->getForm($entity), $this->getProxiableFields($entity));
      }
    }
    $form['proxied']['#type'] = 'container';

    return $form;
  }

  /**
   * Returns all fields of an entity which can be proxied by the widget.
   *
   * @param \Drupal\media_entity\MediaInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The proxiable field definitions, keyed by machine name.
   */
  protected function getProxiableFields(MediaInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    $configurable_fields = array_filter(
      $this->entityManager->getFieldDefinitions($entity_type, $bundle),
      function (FieldDefinitionInterface $field) {
        return $field->isRequired();
      }
    );
    $extra_fields = $this->entityManager->getExtraFields($entity_type, $bundle);
    $fields = array_merge($configurable_fields, $extra_fields['form']);

    $type_configuration = $entity->getType()->getConfiguration();
    $source_field = $type_configuration['source_field'];
    unset ($fields[$source_field]);

    return $fields;
  }

  /**
   * AJAX callback. Returns the proxied fields for the media entity.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array
   *   The proxied fields' container element.
   */
  public function getProxiedFields(array &$form, FormStateInterface $form_state) {
    return $form['widget']['proxied'];
  }

  /**
   * Generates a media entity from an embed code.
   *
   * @param string $embed_code
   *   The embed code.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The new, unsaved media entity.
   */
  protected function generateEntity($embed_code) {
    $bundle = $this->bundleResolver->getBundleFromEmbedCode($embed_code);

    if ($bundle) {
      /** @var \Drupal\media_entity\MediaInterface $entity */
      $entity = $this->entityManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        'name' => 'TODO',
        'uid' => $this->currentUser->id(),
        'status' => TRUE,
      ]);
      $type_configuration = $bundle->getTypeConfiguration();
      $entity->set($type_configuration['source_field'], $embed_code);

      return $entity;
    }
  }

}

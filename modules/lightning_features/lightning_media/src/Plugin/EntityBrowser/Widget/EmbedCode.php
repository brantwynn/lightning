<?php

/**
 * @file
 * Contains \Drupal\lightning_media\Plugin\EntityBrowser\Widget\EmbedCode.
 */

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\lightning_media\FieldProxy;
use Drupal\lightning_media\MediaBundleResolver;
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
   * The media bundle resolver.
   *
   * @var \Drupal\lightning_media\MediaBundleResolver
   */
  protected $bundleResolver;

  /**
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  protected $fieldProxy;

  /**
   * EmbedCode constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\lightning_media\MediaBundleResolver $bundle_resolver
   *   The media bundle resolver.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The currently logged in user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, MediaBundleResolver $bundle_resolver, AccountInterface $current_user, FieldProxy $field_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->bundleResolver = $bundle_resolver;
    $this->currentUser = $current_user;
    $this->fieldProxy = $field_proxy;
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
      $container->get('lightning_media.field_proxy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = array();

    $form['embed_code'] = array(
      '#type' => 'textarea',
      '#placeholder' => $this->t('Enter a URL...'),
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
        $type_configuration = $entity->getType()->getConfiguration();
        $form['proxied'] = $this->fieldProxy->getProxyFields($entity, (array) $type_configuration['source_field']);
      }
    }
    $form['proxied']['#type'] = 'container';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $embed_code = $form_state->getValue('embed_code');
    $bundle = $this->bundleResolver->getBundleFromEmbedCode($embed_code);
    if ($bundle) {
      $type_configuration = $bundle->getTypeConfiguration();

      /** @var \Drupal\media_entity\MediaInterface $entity */
      $entity = $this->entityManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        $type_configuration['source_field'] => $embed_code,
      ]);
      foreach ($form_state->getValues() as $key => $value) {
        if ($entity->hasField($key)) {
          $entity->set($key, $value);
        }
      }
      $entity->save();
      $this->selectEntities([$entity], $form_state);
    }
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

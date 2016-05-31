<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\inline_entity_form\Element\InlineEntityForm;
use Drupal\lightning_media\BundleResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class EntityFormProxy extends WidgetBase {

  /**
   * The media bundle resolver.
   *
   * @var BundleResolverInterface
   */
  protected $bundleResolver;

  /**
   * The currently logged in user.
   *
   * @var AccountInterface
   */
  protected $currentUser;

  abstract protected function getInputValue(FormStateInterface $form_state);

  /**
   * EmbedCode constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param BundleResolverInterface $bundle_resolver
   *   The media bundle resolver.
   * @param AccountInterface $current_user
   *   The currently logged in user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, BundleResolverInterface $bundle_resolver, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->bundleResolver = $bundle_resolver;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $bundle_resolver = $plugin_definition['bundle_resolver'];

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('entity.manager'),
      $container->get('plugin.manager.lightning_media.bundle_resolver')->createInstance($bundle_resolver),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = array(
      'entity' => array(
        '#markup' => NULL,
      ),
      'ief_target' => array(
        '#type' => 'container',
        '#id' => 'ief-target',
        '#weight' => 10,
      ),
    );

    $input = $this->getInputValue($form_state);
    if ($input) {
      $entity = $this->generateEntity($input);
      if ($entity) {
        $form['entity'] = array(
          '#type' => 'inline_entity_form',
          '#entity_type' => $entity->getEntityTypeId(),
          '#bundle' => $entity->bundle(),
          '#default_value' => $entity,
          '#form_mode' => 'media_browser',
          '#process' => array(
            [InlineEntityForm::class, 'processEntityForm'],
            [$this, 'processEntityForm'],
          ),
        );
      }
    }

    return $form;
  }

  public function processEntityForm(array $entity_form) {
    return $entity_form;
  }

  public function getEntityForm(array &$form, FormStateInterface $form_state) {
    return $form['widget']['entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state) {
    $input = $this->getInputValue($form_state);

    $bundle = $this->bundleResolver->getBundle($input);
    if ($bundle) {
      $type_config = $bundle->getTypeConfiguration();

      /** @var \Drupal\media_entity\MediaInterface $entity */
      $entity = $this->entityManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        $type_config['source_field'] => $input,
      ]);
      foreach ($form_state->getValues() as $key => $value) {
        if ($entity->hasField($key)) {
          $entity->set($key, $value);
        }
      }
      $entity->save();

      // Complete the selection process.
      $this->selectEntities([$entity], $form_state);
    }
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
  protected function generateEntity($input) {
    $bundle = $this->bundleResolver->getBundle($input);

    if ($bundle) {
      /** @var \Drupal\media_entity\MediaInterface $entity */
      $entity = $this->entityManager->getStorage('media')->create([
        'bundle' => $bundle->id(),
        'name' => 'TODO',
        'uid' => $this->currentUser->id(),
        'status' => TRUE,
      ]);
      $type_config = $bundle->getTypeConfiguration();
      $entity->set($type_config['source_field'], $input);

      return $entity;
    }
  }

}

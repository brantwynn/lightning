<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
use Drupal\lightning_media\BundleResolverInterface;
use Drupal\lightning_media\FieldProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class ProxyWidgetBase extends WidgetBase {

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

  /**
   * The field proxy service.
   *
   * @var FieldProxy
   */
  protected $fieldProxy;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, BundleResolverInterface $bundle_resolver, AccountInterface $current_user, FieldProxy $field_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->bundleResolver = $bundle_resolver;
    $this->currentUser = $current_user;
    $this->fieldProxy = $field_proxy;
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
      $container->get('current_user'),
      $container->get('lightning_media.field_proxy')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = array(
      'proxy_fields' => array(
        '#type' => 'container',
      ),
    );

    $input = $this->getInputValue($form_state);
    if ($input) {
      $entity = $this->generateEntity($input);
      if ($entity) {
        $type_config = $entity->getType()->getConfiguration();
        $form['proxy_fields'] += $this->fieldProxy->getProxyFields($entity, (array) $type_config['source_field']);
      }
    }

    return $form;
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
  public function onInput(array &$form, FormStateInterface $form_state, Request $request, Response $response = NULL) {
    $response = $response ?: new AjaxResponse();

    // In certain circumstances, $form might be the triggering element, rather
    // than the complete form. I'm not sure if this is a bug in core or what,
    // but to work around it we can explicitly retrieve the complete form.
    $complete_form = $form_state->getCompleteForm();

    $fields = $complete_form['widget']['proxy_fields'];
    foreach (Element::children($fields) as $field) {
      $command = new AppendCommand('#edit-proxy-fields', $fields[$field]);
      $response->addCommand($command);
    }
    return $response;
  }

  public function onClear(array &$form, FormStateInterface $form_state, Request $request, Response $response = NULL) {
    $response = $response ?: new AjaxResponse();

    $command = new InvokeCommand('#edit-proxy-fields', 'empty');
    $response->addCommand($command);
    return $response;
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

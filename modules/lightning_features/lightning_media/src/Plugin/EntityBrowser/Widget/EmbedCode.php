<?php

/**
 * @file
 * Contains \Drupal\lightning_media\Plugin\EntityBrowser\Widget\EmbedCode.
 */

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_browser\WidgetBase;
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
   * @var \Drupal\lightning_media\MediaBundleResolver
   */
  protected $bundleResolver;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventDispatcherInterface $event_dispatcher, EntityManagerInterface $entity_manager, MediaBundleResolver $bundle_resolver, AccountInterface $current_user, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $entity_manager);
    $this->bundleResolver = $bundle_resolver;
    $this->currentUser = $current_user;
    $this->renderer = $renderer;
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
      $container->get('renderer')
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
        'wrapper' => 'preview',
        'callback' => [$this, 'getPreview'],
      ),
    );
    $form['preview'] = array(
      '#prefix' => '<div id="preview">',
      '#suffix' => '</div>',
    );

    $entity = $form_state->get('entity');
    if ($entity && $form_state->isSubmitted() == FALSE) {
      $entity->delete();
    }

    if ($embed_code = $form_state->getValue('embed_code')) {
      $preview = $this->generatePreview($embed_code);

      if (isset($preview['#media'])) {
        $form_state->set('entity', $preview['#media'])->setCached();
      }

      unset($preview['#prefix'], $preview['#suffix']);
      $form['preview'] = array_merge($form['preview'], $preview);
    }

    return $form;
  }

  public function getPreview(array &$form, FormStateInterface $form_state) {
    return $form['widget']['preview'];
  }

  protected function generatePreview($embed_code) {
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
      $entity->set($type_configuration['source_field'], $embed_code)->save();

      return $this->entityManager->getViewBuilder('media')->view($entity);
    }
    else {
      return [];
    }
  }

}

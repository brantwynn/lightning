<?php

/**
 * @file
 * Contains \Drupal\lightning_media\MediaForm.
 */

namespace Drupal\lightning_media;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\media_entity\MediaForm as BaseMediaForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Null;

/**
 * A Lightning-specific version of the default media entity form.
 */
class MediaForm extends BaseMediaForm {

  /**
   * @var \Drupal\lightning_media\MediaBundleResolver
   */
  protected $bundleResolver;

  /**
   * MediaForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\lightning_media\MediaBundleResolver $bundle_resolver
   *   The media bundle resolver.
   */
  public function __construct(EntityManagerInterface $entity_manager, MediaBundleResolver $bundle_resolver) {
    parent::__construct($entity_manager);
    $this->bundleResolver = $bundle_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('lightning.media.bundle_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\media_entity\MediaInterface $entity */
    $entity = $this->getEntity();
    $source_field = $entity->getType()->getConfiguration()['source_field'];
    $fields = $this->entityManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

    $field_type = $fields[$source_field]->getType();
    if ($field_type == 'file' || $field_type == 'image') {
      $form[$source_field]['widget'][0]['#process'][] = [$this, 'setManagedFileAjaxCallback'];
    }
    else {
      $form[$source_field]['widget'][0]['#process'][] = [$this, 'prepareAjaxPreview'];
    }

    return $form;
  }

  /**
   * #process callback for file and image widgets.
   *
   * @param array $element
   *   The widget element.
   *
   * @return array
   *   The modified element.
   */
  public function setManagedFileAjaxCallback(array $element) {
    $element['upload_button']['#ajax']['callback'] =
    $element['remove_button']['#ajax']['callback'] = [$this, 'uploadAjaxCallback'];
    return $element;
  }

  /**
   * #process callback for textual widgets (i.e., embed codes).
   *
   * @param array $element
   *   The widget element.
   *
   * @return array
   *   The modified elements.
   */
  public function prepareAjaxPreview(array $element) {
    // We need a place to put the rendered preview, but for extremely bizarre
    // and Drupal-ey reasons, it needs to be registered with the form system as
    // a container. Don't ask...I spent hours trying to figure this sh!t out.
    $element['preview']['#type'] = 'container';

    $element['value']['#ajax'] = [
      'event' => 'change',
      'callback' => [$this, 'previewAjaxCallback'],
    ];

    return $element;
  }

  /**
   * AJAX callback. Toggles the #metadata field group.
   *
   * @param array $form
   *   The complete, rebuilt form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function uploadAjaxCallback(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return $this->toggleMetadata($response);
  }

  /**
   * Adds an AJAX command to toggle the visibility of the #metadata field group.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  protected function toggleMetadata(AjaxResponse $response) {
    return $response
      ->addCommand(new InvokeCommand('#metadata', 'toggleClass', ['visually-hidden']));
  }

  /**
   * AJAX callback. Generates a preview from an embed code.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function previewAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Get the containing element in order to access the preview element, since
    // we need to get its selector.
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#array_parents'];
    array_pop($parents);
    $element = NestedArray::getValue($form, $parents);

    $preview = $this->generatePreview($element['value']['#value']);
    $selector = '[data-drupal-selector="' . $element['preview']['#attributes']['data-drupal-selector'] . '"]';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($selector, $preview));
    return $this->toggleMetadata($response);
  }

  /**
   * Generates a preview from an embed code.
   *
   * @param string $embed_code
   *   The embed code.
   *
   * @return array|NULL
   *   A renderable array, or NULL if the embed code could not be resolved to
   *   an existing media bundle.
   */
  protected function generatePreview($embed_code) {
    if ($embed_code) {
      $bundle = $this->bundleResolver->getBundleFromEmbedCode($embed_code);

      if ($bundle) {
        /** @var \Drupal\media_entity\MediaInterface $entity */
        $entity = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => $bundle->id(),
          'name' => 'TODO',
          'uid' => $this->currentUser()->id(),
          'status' => TRUE,
        ]);
        $type_configuration = $bundle->getTypeConfiguration();
        $entity->set($type_configuration['source_field'], $embed_code);

        return $this->entityManager->getViewBuilder('media')->view($entity);
      }
    }
  }

}

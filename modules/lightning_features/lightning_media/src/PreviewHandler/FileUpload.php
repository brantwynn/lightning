<?php

namespace Drupal\lightning_media\PreviewHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\lightning_media\PreviewHandlerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Preview handler for file upload fields (file and image).
 */
class FileUpload extends PreviewHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, EntityInterface $entity = NULL) {
    parent::alterForm($form, $form_state, $entity);

    $entity = $entity ?: $this->getEntity($form_state);
    $field = $this->getField($entity)->getName();

    $form[$field]['widget'][0]['#process'][] = [$this, 'setCallback'];
  }

  /**
   * Process callback. Sets the AJAX callback to static::uploadCallback().
   *
   * @param array $element
   *   The file_managed element being processed.
   *
   * @return array
   *   The processed element.
   */
  public function setCallback(array $element) {
    $callback = [$this, 'uploadCallback'];
    $element['upload_button']['#ajax']['callback'] = $callback;
    $element['remove_button']['#ajax']['callback'] = $callback;

    return $element;
  }

  /**
   * AJAX callback wrapping around the default callback for file_managed fields.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming HTTP request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The AJAX response.
   */
  public function uploadCallback(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return static::toggleMetaData($response);
  }

}

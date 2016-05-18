<?php

namespace Drupal\lightning_media;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\HttpFoundation\Request;

class FileUploadPreviewHandler extends PreviewHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->getEntity($form_state);
    $field = $this->getField($entity)->getName();

    $form[$field]['widget'][0]['#process'][] = [$this, 'setCallback'];
    parent::alterForm($form, $form_state);
  }

  public function setCallback(array $element) {
    $callback = [__CLASS__, 'uploadCallback'];
    $element['upload_button']['#ajax']['callback'] = $callback;
    $element['remove_button']['#ajax']['callback'] = $callback;

    return $element;
  }

  public static function uploadCallback(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return static::toggleMetaData($response);
  }

}

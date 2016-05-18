<?php

namespace Drupal\lightning_media;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Symfony\Component\HttpFoundation\Request;

class FileUploadPreviewHandler extends PreviewHandlerBase {

  use DependencySerializationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    parent::alterForm($form, $form_state);

    $entity = $this->getEntity($form_state);
    $field = $this->getField($entity)->getName();

    $form[$field]['widget'][0]['#process'][] = [$this, 'setCallback'];
  }

  public function setCallback(array $element) {
    $callback = [$this, 'uploadCallback'];
    $element['upload_button']['#ajax']['callback'] = $callback;
    $element['remove_button']['#ajax']['callback'] = $callback;

    return $element;
  }

  public function uploadCallback(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return static::toggleMetaData($response);
  }

}

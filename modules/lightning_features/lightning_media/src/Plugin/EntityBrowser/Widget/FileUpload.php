<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
use Drupal\file\FileInterface;
use Drupal\lightning_media\FieldProxy;
use Symfony\Component\HttpFoundation\Request;

/**
 * An Entity Browser widget for creating media entities from uploaded files.
 *
 * @EntityBrowserWidget(
 *   id = "file_upload",
 *   label = @Translation("File Upload"),
 *   description = @Translation("Allows creation of media entities from file uploads."),
 *   bundle_resolver = "file_upload"
 * )
 */
class FileUpload extends ProxyWidgetBase {

  /**
   * {@inheritdoc}
   */
  protected function getInputValue(FormStateInterface $form_state) {
    $value = $form_state->getValue('file');
    if ($value) {
      return $this->entityManager->getStorage('file')->load($value[0]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['file'] = array(
      '#type' => 'managed_file',
      '#title' => $this->t('File'),
      '#process' => [
        [ManagedFile::class, 'processManagedFile'],
        [$this, 'processFileElement'],
      ]
    );

    return $form;
  }

  public function processFileElement(array $element) {
    $element['upload_button']['#ajax']['callback'] = [$this, 'onUpload'];
    $element['remove_button']['#ajax']['callback'] = [$this, 'onRemove'];
    return $element;
  }

  public function onUpload(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return $this->onInput($form, $form_state, $request, $response);
  }

  public function onRemove(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);
    return $this->onClear($form, $form_state, $request, $response);
  }

}

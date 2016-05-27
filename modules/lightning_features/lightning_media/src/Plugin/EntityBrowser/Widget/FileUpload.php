<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Element\ManagedFile;
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
class FileUpload extends EntityFormProxy {

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
        [$this, 'processInitialFileElement'],
      ]
    );

    return $form;
  }

  public function processInitialFileElement(array $element) {
    $element['upload_button']['#ajax']['callback'] = [$this, 'onUpload'];
    $element['remove_button']['#value'] = $this->t('Cancel');
    $element['remove_button']['#ajax']['callback'] = [$this, 'onRemove'];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function processEntityForm(array $entity_form) {
    $type_config = $entity_form['#entity']->getType()->getConfiguration();
    $field = $type_config['source_field'];

    if (isset($entity_form[$field])) {
      $entity_form[$field]['widget'][0]['#process'][] = [$this, 'processEntityFormFileElement'];
    }

    return parent::processEntityForm($entity_form);
  }

  public function processEntityFormFileElement(array $element, FormStateInterface $form_state, array &$complete_form) {
    $element['remove_button']['#access'] = FALSE;

    if ($element['#default_value']) {
      $key = 'file_' . $element['#default_value']['target_id'];
      $element[$key]['#access'] = FALSE;
    }

    return $element;
  }

  public function onUpload(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);

    $complete_form = $form_state->getCompleteForm();
    $selector = '#' . $complete_form['widget']['ief_target']['#id'];
    $content = $this->getEntityForm($complete_form, $form_state);

    $command = new HtmlCommand($selector, $content);
    $response->addCommand($command);
    return $response;
  }

  public function onRemove(array &$form, FormStateInterface $form_state, Request $request) {
    $response = ManagedFile::uploadAjaxCallback($form, $form_state, $request);

    $complete_form = $form_state->getCompleteForm();
    $selector = '#' . $complete_form['widget']['ief_target']['#id'];

    $command = new InvokeCommand($selector, 'empty');
    return $response->addCommand($command);
  }

}
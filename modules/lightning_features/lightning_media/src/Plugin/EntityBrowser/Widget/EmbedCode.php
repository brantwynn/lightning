<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;

/**
 * An Entity Browser widget for creating media entities from embed codes.
 *
 * @EntityBrowserWidget(
 *   id = "embed_code",
 *   label = @Translation("Embed Code"),
 *   description = @Translation("Allows creation of media entities from embed codes."),
 *   bundle_resolver = "embed_code"
 * )
 */
class EmbedCode extends EntityFormProxy {

  /**
   * {@inheritdoc}
   */
  protected function getInputValue(FormStateInterface $form_state) {
    return $form_state->getValue('embed_code');
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $form = parent::getForm($original_form, $form_state, $additional_widget_parameters);

    $form['embed_code'] = array(
      '#type' => 'textarea',
      '#placeholder' => $this->t('Enter a URL...'),
      '#ajax' => array(
        'event' => 'change',
        'wrapper' => $form['ief_target']['#id'],
        'method' => 'html',
        'callback' => [$this, 'getEntityForm'],
      ),
    );

    return $form;
  }

}

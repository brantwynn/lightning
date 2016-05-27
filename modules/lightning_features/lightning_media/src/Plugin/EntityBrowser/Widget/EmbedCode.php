<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\lightning_media\FieldProxy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
class EmbedCode extends ProxyWidgetBase {

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
        'callback' => [$this, 'onInput'],
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function onInput(array &$form, FormStateInterface $form_state, Request $request, Response $response = NULL) {
    $input = $this->getInputValue($form_state);

    if ($input) {
      return parent::onInput($form, $form_state, $request, $response);
    }
    else {
      return $this->onClear($form, $form_state, $request, $response);
    }
  }

}

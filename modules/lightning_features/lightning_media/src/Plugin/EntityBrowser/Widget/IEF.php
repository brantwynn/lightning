<?php

namespace Drupal\lightning_media\Plugin\EntityBrowser\Widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_browser\WidgetBase;

/**
 * An Entity Browser widget for creating entities on the fly using IEF.
 *
 * @EntityBrowserWidget(
 *   id = "ief",
 *   label = @Translation("Inline Entity Form"),
 *   description = @Translation("Allows creation of entities using an inline form.")
 * )
 */
class IEF extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters) {
    $configuration = $this->getConfiguration();

    return [
      'ief' => [
        '#type' => 'inline_entity_form',
        '#entity_type' => $configuration['settings']['entity_type'],
        '#bundle' => $configuration['settings']['bundle'],
      ],
    ];
  }

}

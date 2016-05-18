<?php

namespace Drupal\lightning_media;

use Drupal\Core\Form\FormStateInterface;

interface PreviewHandlerInterface {

  public function extraFields($bundle);

  public function alterForm(array &$form, FormStateInterface $form_state);

}

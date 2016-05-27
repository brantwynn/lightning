<?php

namespace Drupal\lightning_media;

/**
 * Interface for plugins which determine which media bundle(s) are appropriate
 * for handling an input value.
 */
interface BundleResolverInterface {

  /**
   * Attempts to determine the media bundle applicable for an input value.
   *
   * @param mixed $input
   *   The input value.
   *
   * @return \Drupal\media_entity\MediaBundleInterface|FALSE
   *   The applicable media bundle, or FALSE if there isn't one.
   */
  public function getBundle($input);

}

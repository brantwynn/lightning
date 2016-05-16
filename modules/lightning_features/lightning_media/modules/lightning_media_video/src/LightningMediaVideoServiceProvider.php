<?php

namespace Drupal\lightning_media_video;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

class LightningMediaVideoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // This is the equivalent of '@service_id' in a *.services.yml file.
    $reference = new Reference('video_embed_field.provider_manager');

    $container
      ->getDefinition('lightning.media.bundle_resolver')
      ->addArgument($reference);
  }

}

<?php

namespace Drupal\lightning_media;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\video_embed_field\ProviderManagerInterface;

class MediaBundleResolver {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $bundleStorage;

  /**
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedData;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $videoProviders;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, TypedDataManagerInterface $typed_data_manager, ModuleHandlerInterface $module_handler, ProviderManagerInterface $video_providers = NULL) {
    $this->bundleStorage = $entity_type_manager->getStorage('media_bundle');
    $this->typedData = $typed_data_manager;
    $this->moduleHandler = $module_handler;
    $this->videoProviders = $video_providers;
  }

  /**
   * @param $embed_code
   * @return \Drupal\media_entity\MediaBundleInterface|FALSE
   */
  public function getBundleFromEmbedCode($embed_code) {
    switch (TRUE) {
      case $this->isVideo($embed_code):
        return $this->bundleStorage->load('video');

      case $this->isTweet($embed_code):
        return $this->bundleStorage->load('tweet');

      case $this->isInstagram($embed_code):
        return $this->bundleStorage->load('instagram');

      default:
        return FALSE;
    }
  }

  public function isVideo($embed_code) {
    if ($this->videoProviders) {
      return (boolean) $this->videoProviders->loadProviderFromInput($embed_code);
    }
    else {
      return FALSE;
    }
  }

  public function isTweet($embed_code) {
    if ($this->moduleHandler->moduleExists('media_entity_twitter')) {
      return $this->validateStringAs('TweetEmbedCode', $embed_code);
    }
    else {
      return FALSE;
    }
  }

  public function isInstagram($embed_code) {
    if ($this->moduleHandler->moduleExists('media_entity_instagram')) {
      return $this->validateStringAs('InstagramEmbedCode', $embed_code);
    }
    else {
      return FALSE;
    }
  }

  protected function validateStringAs($constraint, $input) {
    $definition = $this->typedData->createDataDefinition('string');
    $definition->addConstraint($constraint);
    $value = StringData::createInstance($definition);
    $value->setValue($input);

    return $value->validate()->count() == 0;
  }

}

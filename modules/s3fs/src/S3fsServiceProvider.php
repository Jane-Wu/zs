<?php

namespace Drupal\s3fs;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The stream wrapper class.
 *
 * In the docs for this class, anywhere you see "<scheme>", it can mean either
 * "s3" or "public", depending on which stream is currently being serviced.
 */
class S3fsServiceProvider implements ServiceModifierInterface {

  /**
   * Modifies existing service definitions.
   *
   * @param ContainerBuilder $container
   *   The ContainerBuilder whose service definitions can be altered.
   */
  public function alter(ContainerBuilder $container) {
    $cssdefinition = $container->getDefinition('asset.css.dumper');
    $cssdefinition->setClass('Drupal\s3fs\S3fsStreamOverrideManager');
    $cssdefinition->addArgument(new Reference('config.factory'));
    $jsdefinition = $container->getDefinition('asset.js.dumper');
    $jsdefinition->setClass('Drupal\s3fs\S3fsStreamOverrideManager');
    $jsdefinition->addArgument(new Reference('config.factory'));
  }
}

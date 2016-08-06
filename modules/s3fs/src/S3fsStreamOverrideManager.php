<?php

namespace Drupal\s3fs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetDumperInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Overrides default LanguageManager to provide configured languages.
 */
class S3fsStreamOverrideManager implements AssetDumperInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a S3fsStreamOverrideManager instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Dumps an (optimized) asset to persistent storage.
   *
   * @param string $data
   *   An (optimized) asset's contents.
   * @param string $file_extension
   *   The file extension of this asset.
   *
   * @return string
   *   An URI to access the dumped asset.
   */
  public function dump($data, $file_extension) {
    $s3fs_config = $this->configFactory->get('s3fs.settings');
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = $file_extension . '_' . Crypt::hashBase64($data) . '.' . $file_extension;
    // Create the css/ or js/ path within the S3 bucket.
    if ($s3fs_config->get('use_s3_for_public')) {
      if ($s3fs_config->get('no_rewrite_cssjs')) {
        $path = 's3fs://' . $file_extension;
      }
      else {
        $path = ($file_extension === 'css') ? "{$GLOBALS['base_url']}/s3fs-css/css" : "{$GLOBALS['base_url']}/s3fs-js/js";
        $uri = $path . '/' . $filename;
        return $uri;
      }
    }
    else {
      $path = 'public://' . $file_extension;
    }

    $uri = $path . '/' . $filename;
    // Create the CSS or JS file.
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    if (!file_exists($uri) && !file_unmanaged_save_data($data, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    // If CSS/JS gzip compression is enabled and the zlib extension is available
    // then create a gzipped version of this file. This file is served
    // conditionally to browsers that accept gzip using .htaccess rules.
    // It's possible that the rewrite rules in .htaccess aren't working on this
    // server, but there's no harm (other than the time spent generating the
    // file) in generating the file anyway. Sites on servers where rewrite rules
    // aren't working can set css.gzip to FALSE in order to skip
    // generating a file that won't be used.
    if (extension_loaded('zlib') && $this->configFactory->get('system.performance')->get($file_extension . '.gzip')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
    }
    return $uri;
  }

}

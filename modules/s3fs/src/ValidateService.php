<?php

namespace Drupal\s3fs;

use Drupal\Core\Database\Connection;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Config\ConfigFactory;

/**
 * Defines a ValidateService service.
 */
class ValidateService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs an ValidateService object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The new database connection object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(Connection $connection, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * Validate the S3fs config.
   *
   * @param $config
   *   Array of configuration settings from which to configure the client.
   * @param $returnError
   *   Boolean, False by default.
   *
   * @return Boolean/array
   */
  public function validate(array $config, $returnError = FALSE) {
    if (!empty($config['use_customhost']) && empty($config['hostname'])) {
      if ($returnError) {
        $name = 'hostname';
        $msg = t('You must specify a Hostname to use the Custom Host feature.');
        return [$name, $msg];
      }
      return FALSE;
    }
    if (!empty($config['use_cname']) && empty($config['domain'])) {
      if ($returnError) {
        $name = 'domain';
        $msg = t('You must specify a CDN Domain Name to use the CNAME feature.');
        return [$name, $msg];
      }
      return FALSE;
    }

    try {
      $s3 = $this->getAmazonS3Client($config);
    }
    catch (S3Exception $e) {
      if ($returnError) {
        $name = 'form';
        $msg = $e->getMessage();
        return [$name, $msg];
      }
      return FALSE;
    }

    // Test the connection to S3, and the bucket name.
    try {
      // listObjects() will trigger descriptive exceptions if the credentials,
      // bucket name, or region are invalid/mismatched.
      $s3->listObjects(['Bucket' => $config['bucket'], 'MaxKeys' => 1]);
    }
    catch (S3Exception $e) {
      if ($returnError) {
        $name = 'form';
        $msg = t('An unexpected error occurred. @message', ['@message' => $e->getMessage()]);
        return [$name, $msg];
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets up the S3Client object.
   * For performance reasons, only one S3Client object will ever be created
   * within a single request.
   *
   * @param $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The fully-configured S3Client object.
   *
   * @throws \Drupal\s3fs\S3fsException
   */
  public function getAmazonS3Client($config) {
    $s3 = drupal_static(__METHOD__);
    $static_config = drupal_static(__METHOD__);

    // If the client hasn't been set up yet, or the config given to this call is
    // different from the previous call, (re)build the client.
    if (!isset($s3) || $static_config != $config) {
      $savedConfig = $this->configFactory->get('s3fs.settings')->getRawData();
      // For the SDK credentials, get the saved settings from _s3fs_get_setting(). But since $config might be populated
      // with to-be-validated settings, its contents (if set) override the saved settings.
      $access_key = $savedConfig['access_key'];
      if (isset($config['access_key'])) {
        $access_key = $config['access_key'];
      }
      $secret_key = $savedConfig['secret_key'];
      if (isset($config['secret_key'])) {
        $secret_key = $config['secret_key'];
      }
      $use_instance_profile = $savedConfig['use_instance_profile'];
      if (isset($config['use_instance_profile'])) {
        $use_instance_profile = $config['use_instance_profile'];
      }
      $default_cache_config = $savedConfig['default_cache_config'];
      if (isset($config['default_cache_config'])) {
        $default_cache_config = $config['default_cache_config'];
      }

      if (!class_exists('Aws\S3\S3Client')) {
        throw new S3fsException(
          t('Cannot load Aws\S3\S3Client class. Please ensure that the awssdk2 library is installed correctly.')
        );
      }
      else {
        if (!$use_instance_profile && (!$secret_key || !$access_key)) {
          throw new S3fsException(t("Your AWS credentials have not been properly configured.
        Please set them on the S3 File System admin/config/media/s3fs page or
        set \$conf['awssdk2_access_key'] and \$conf['awssdk2_secret_key'] in settings.php."));
        }
        else {
          if ($use_instance_profile && empty($default_cache_config)) {
            throw new s3fsException(t("Your AWS credentials have not been properly configured.
        You are attempting to use instance profile credentials but you have not set a default cache location.
        Please set it on the admin/config/media/s3fs page or set \$conf['awssdk2_default_cache_config'] in settings.php."));
          }
        }
      }

      // Create the Aws\S3\S3Client object.
      if ($use_instance_profile) {
        $client_config = ['default_cache_config' => $default_cache_config];
      }
      else {
        $client_config['credentials'] = [
          'key' => $access_key,
          'secret' => $secret_key,
        ];
      }
      if (!empty($config['region'])) {
        $client_config['region'] = $config['region'];
        // Signature v4 is only required in the Beijing and Frankfurt regions.
        // Also, setting it will throw an exception if a region hasn't been set.
        $client_config['signature'] = 'v4';
      }
      if (!empty($config['use_customhost']) && !empty($config['hostname'])) {
        $client_config['base_url'] = $config['hostname'];
      }
      $client_config['version'] = 'latest';
      $s3 = S3Client::factory($client_config);
    }
    $static_config = $config;
    return $s3;
  }

  /**
   * Copies all the local files from the specified file system into S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   * @param $scheme
   *   A variable defining which scheme (Public or Private) to copy.
   */
  function copyFileSystemToS3($config, $scheme) {
    if ($scheme == 'public') {
      $source_folder = realpath(PublicStream::basePath());
      $target_folder = !empty($config['public_folder']) ? $config['public_folder'] . '/' : 's3fs-public/';
    }
    else {
      if ($scheme == 'private') {
        $source_folder = (PrivateStream::basePath() ? PrivateStream::basePath() : '');
        $source_folder_real = realpath($source_folder);
        if (empty($source_folder) || empty($source_folder_real)) {
          drupal_set_message('Private file system base path is unknown. Unable to perform S3 copy.', 'error');
          return;
        }
        $target_folder = !empty($config['private_folder']) ? $config['private_folder'] . '/' : 's3fs-private/';
      }
    }

    if (!empty($config['root_folder'])) {
      $target_folder = "{$config['root_folder']}/$target_folder";
    }

    // Create S3 object to move files.
    $s3 = $this->getAmazonS3Client($config);

    $file_paths = $this->dirScan($source_folder);
    foreach ($file_paths as $path) {
      $relative_path = str_replace($source_folder . '/', '', $path);
      print "Copying $scheme://$relative_path into S3...\n";
      // Finally get to make use of S3fsStreamWrapper's "S3 is actually a local
      // file system. No really!" functionality.
      copy($path, "s3fs://$relative_path");
    }

    drupal_set_message(t('Copied all local %scheme files to S3.', ['%scheme' => $scheme]), 'status');
  }

  /**
   * Scans a given directory.
   *
   * @param $dir
   *   The directory to be scanned.
   *
   * @return array
   *   Array of file paths.
   */
  function dirScan($dir) {
    $output = array();
    $files = scandir($dir);
    foreach ($files as $file) {
      $path = "$dir/$file";

      if ($file != '.' && $file != '..') {
        // In case they put their private root folder inside their public one,
        // skip it. When listing the private file system contents, $path will
        // never trigger this.
        if ($path == realpath(PrivateStream::basePath() ? PrivateStream::basePath() : '')) {
          continue;
        }

        if (is_dir($path)) {
          $output = array_merge($output, $this->dirScan($path));
        }
        else {
          $output[] = $path;
        }
      }
    }
    return $output;
  }

}

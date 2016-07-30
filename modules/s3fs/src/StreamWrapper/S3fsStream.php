<?php

namespace Drupal\s3fs\StreamWrapper;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\HttpFoundation\Request;
use Aws\S3\S3Client;
use Aws\S3\Exception;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\Stream;
use Drupal\s3fs\S3fsException;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;

/**
 * Defines a Drupal s3fs (s3fs://) stream wrapper class.
 *
 * Provides support for storing files on the amazon s3 file system with the
 * Drupal file interface.
 */
class S3fsStream implements StreamWrapperInterface {

  /** @var StreamInterface Underlying stream resource */
  private $body;

  /**
   * A generic resource handle.
   *
   * @var resource
   */
  public $handle = NULL;

  /**
   * Instance URI (stream).
   *
   * A stream is referenced as "scheme://target".
   *
   * @var string
   */
  protected $uri;

  /**
   * The AWS SDK for PHP S3Client object.
   *
   * @var \Aws\S3\S3Client
   */
  protected $s3 = NULL;

  /**
   * Domain we use to access files over http.
   *
   * @var string
   */
  protected $domain = NULL;

  /**
   * Directory listing used by the dir_* methods.
   *
   * @var array
   */
  protected $dir = NULL;

  /**
   * Map for files that should be delivered with a torrent URL.
   *
   * @var array
   */
  protected $torrents = array();

  /**
   * Files that the user has said must be downloaded, rather than viewed.
   *
   * @var array
   */
  protected $saveas = array();

  /**
   * Files which should be created with URLs that eventually time out.
   *
   * @var array
   */
  protected $presignedURLs = array();

  /**
   * The constructor sets this to TRUE once it's finished.
   *
   * See the comment on _assert_constructor_called() for why this exists.
   *
   * @var bool
   */
  protected $constructed = FALSE;

  /**
   * Default map for determining file mime types.
   *
   * @var array
   */
  protected static $mimeTypeMapping = NULL;

  /**
   * Indicates the current error state in the wrapper.
   *
   * This allows _trigger_error() to tell other stream_* functions to return
   * FALSE when the wrapper encounters an error.
   *
   * @var bool
   */
  protected $_error_state = FALSE;

  /**
   * S3fsStream constructor.
   *
   * Creates the \Aws\S3\S3Client client object and activates the options
   * specified on the S3 File System Settings page.
   */
  public function __construct() {
    // Since S3fsStreamWrapper is always constructed with the same inputs (the
    // file URI is not part of construction), store the constructed settings
    // statically. This is important for performance because Drupal
    // re-constructs stream wrappers very often.
    $settings = &drupal_static('S3fsStream_constructed_settings');
    if ($settings !== NULL) {
      $this->config = $settings['config'];
      $this->getClient();
      $this->domain = $settings['domain'];
      $this->torrents = $settings['torrents'];
      $this->presignedURLs = $settings['presignedURLs'];
      $this->saveas = $settings['saveas'];
      $this->constructed = TRUE;
      return;
    }
    $config = \Drupal::config('s3fs.settings');
    $this->getClient();
    foreach ($config->get() as $prop => $value) {
      $this->config[$prop] = $value;
    }

    if (empty($this->config['bucket'])) {
      $msg = t('Your AmazonS3 bucket name is not configured. Please visit the @settings_page.',
        array('@settings_page' => l(t('configuration page'), '/admin/config/media/s3fs/settings')));
      watchdog('S3 File System', $msg, array(), WATCHDOG_ERROR);
      throw new Exception($msg);
    }

    // Get the S3 client object.
    $this->getClient();

    // Always use HTTPS when the page is being served via HTTPS, to avoid
    // complaints from the browser about insecure content.
    global $is_https;
    if ($is_https) {
      // We change the config itself, rather than simply using $is_https in
      // the following if condition, because $this->config['use_https'] gets
      // used again later.
      $this->config['use_https'] = TRUE;
    }

    if (!empty($this->config['use_https'])) {
      $scheme = 'https';
      $this->_debug('Using HTTPS.');
    }
    else {
      $scheme = 'http';
      $this->_debug('Using HTTP.');
    }

    // CNAME support for customizing S3 URLs.
    // If use_cname is not enabled, file URLs do not use $this->domain.
    if (!empty($this->config['use_cname']) && !empty($this->config['domain'])) {
      $domain = check_url($this->config['domain']);
      if ($domain) {
        // If domain is set to a root-relative path, add the hostname back in.
        if (strpos($domain, '/') === 0) {
          $domain = $_SERVER['HTTP_HOST'] . $domain;
        }
        $this->domain = "$scheme://$domain";
      }
      else {
        // Due to the config form's validation, this shouldn't ever happen.
        throw new \Exception(t('The "Use CNAME" option is enabled, but no Domain Name has been set.'));
      }
    }

    // Convert the torrents string to an array.
    if (!empty($this->config['torrents'])) {
      foreach (explode("\n", $this->config['torrents']) as $line) {
        $blob = trim($line);
        if ($blob) {
          $this->torrents[] = $blob;
        }
      }
    }

    // Convert the presigned URLs string to an associative array like
    // array(blob => timeout).
    if (!empty($this->config['presigned_urls'])) {
      foreach (explode(PHP_EOL, $this->config['presigned_urls']) as $line) {
        $blob = trim($line);
        if ($blob) {
          if (preg_match('/(.*)\|(.*)/', $blob, $matches)) {
            $blob = $matches[2];
            $timeout = $matches[1];
            $this->presignedURLs[$blob] = $timeout;
          }
          else {
            $this->presignedURLs[$blob] = 60;
          }
        }
      }
    }

    // Convert the forced save-as string to an array.
    if (!empty($this->config['saveas'])) {
      foreach (explode(PHP_EOL, $this->config['saveas']) as $line) {
        $blob = trim($line);
        if ($blob) {
          $this->saveas[] = $blob;
        }
      }
    }

    // Save all the work we just did, so that subsequent S3fsStreamWrapper
    // constructions don't have to repeat it.
    $settings['config'] = $this->config;
    $settings['domain'] = $this->domain;
    $settings['torrents'] = $this->torrents;
    $settings['presignedURLs'] = $this->presignedURLs;
    $settings['saveas'] = $this->saveas;

    $this->constructed = TRUE;
    $this->_debug('S3fsStream constructed.');
  }

  //
  protected function getClient() {
    $config = \Drupal::config('s3fs.settings');
    if (!empty($config)) {
      $client = S3Client::factory(array(
        'credentials' => array(
          'key' => $config->get('access_key'),
          'secret' => $config->get('secret_key'),
        ),
        'region' => $config->get('region'),
        'version' => 'latest'
      ));
      $this->s3 = $client;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getType() {
    return StreamWrapperInterface::NORMAL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return t('S3 File System');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('Amazon Simple Storage Service.');
  }

  /**
   * Gets the path that the wrapper is responsible for.
   *
   * This function isn't part of DrupalStreamWrapperInterface, but the rest
   * of Drupal calls it as if it were, so we need to define it.
   *
   * @return string
   *   The empty string. Since this is a remote stream wrapper,
   *   it has no directory path.
   */
  public function getDirectoryPath() {
    $this->_debug("getDirectoryPath() called.");

    return '';
  }

  /**
   * Sets the stream resource URI. URIs are formatted as "<scheme>://filepath".
   *
   * @param string $uri
   *   The URI that should be used for this instance.
   */
  public function setUri($uri) {
    $this->_debug("setUri($uri) called.");

    $this->uri = $uri;
  }

  /**
   * Returns the stream resource URI, which looks like "<scheme>://filepath".
   *
   * @return string
   *   The current URI of the instance.
   */
  public function getUri() {
    $this->_debug("getUri() called for {$this->uri}.");

    return $this->uri;
  }

  /**
   * This wrapper does not support realpath().
   *
   * @return bool
   *   Always returns FALSE.
   */
  public function realpath() {
    $this->_debug("realpath() called for {$this->uri}. S3fsStream does not support this function.");

    return FALSE;
  }

  public function moveUploadedFile($filename, $uri) {
    $this->_debug("moveUploadedFile() called for {$this->uri}. S3fsStream does not support this function.");
    return FALSE;
  }

  /**
   * Returns a web accessible URL for the resource.
   *
   * The format of the returned URL will be different depending on how the S3
   * integration has been configured on the S3 File System admin page.
   *
   * @param bool $no_redirect
   *  A custom parameter for internal use by s3fs.
   *
   * @return string
   *   A web accessible URL for the resource.
   */
  public function getExternalUrl() {
    //$this->_debug("getExternalUrl() called for {$this->uri}.");
    //$path = str_replace('\\', '/', $this->getTarget());
    //return $GLOBALS['base_url'] . '/' . self::getDirectoryPath() . '/' . UrlHelper::encodePath($path);

    // In case we're on Windows, replace backslashes with forward-slashes.
    // Note that $uri is the unaltered value of the File's URI, while
    // $s3_key may be changed at various points to account for implementation
    // details on the S3 side (e.g. root_folder, s3fs-public).
    $s3_key = $uri = str_replace('\\', '/', file_uri_target($this->uri));

    // If this is a private:// file, it must be served through the
    // system/files/$path URL, which allows Drupal to restrict access
    // based on who's logged in.
    if (file_uri_scheme($this->uri) == 'private') {
      // Convert backslashes from windows filenames to forward slashes.
      $path = str_replace('\\', '/', $uri);
      $relative_url = Url::fromUserInput("/system/files/$path");
      return \Drupal::l($relative_url, $relative_url);
      //return url("system/files/$path", array('absolute' => TRUE));
    }

    // When generating an image derivative URL, e.g. styles/thumbnail/blah.jpg,
    // if the file doesn't exist, provide a URL to s3fs's special version of
    // image_style_deliver(), which will create the derivative when that URL
    // gets requested.
    $path_parts = explode('/', $uri);
    if ($path_parts[0] == 'styles' && substr($uri, -4) != '.css') {
      if (!$this->_s3fs_get_object($this->uri)) {

        $args = $path_parts;
        array_shift($args);
        $style = array_shift($args);
        $scheme = array_shift($args);
        $filename = implode('/', $args);
        $original_image = "$scheme://$filename";
        // Load the image style configuration entity.
        $style = ImageStyle::load($style);
        $destination = $style->buildUri($original_image);
        $style->createDerivative($original_image, $destination);
      }
    }

    // Deal with public:// files.
    if (file_uri_scheme($this->uri) == 'public') {
      // Rewrite all css/js file paths unless the user has told us not to.
      if (!$this->config['no_rewrite_cssjs']) {
        if (substr($uri, -4) == '.css') {
          // Send requests for public CSS files to /s3fs-css/path/to/file.css.
          // Users must set that path up in the webserver config as a proxy into
          // their S3 bucket's s3fs-public/ folder.
          return "{$GLOBALS['base_url']}/s3fs-css/" . UrlHelper::encodePath($uri);
        }
        else {
          if (substr($uri, -3) == '.js') {
            // Send requests for public JS files to /s3fs-js/path/to/file.js.
            // Like with CSS, the user must set up that path as a proxy.
            return "{$GLOBALS['base_url']}/s3fs-js/" . UrlHelper::encodePath($uri);
          }
        }
      }

      // public:// files are stored in S3 inside the s3fs-public/ folder.
      $s3_key = "s3fs-public/$s3_key";
    }

    // Set up the URL settings as speciied in our settings page.
    $url_settings = array(
      'torrent' => FALSE,
      'presigned_url' => FALSE,
      'timeout' => 60,
      'forced_saveas' => FALSE,
      'api_args' => array('Scheme' => !empty($this->config['use_https']) ? 'https' : 'http'),
      'custom_GET_args' => array(),
    );

    // Presigned URLs.
    foreach ($this->presignedURLs as $blob => $timeout) {
      // ^ is used as the delimeter because it's an illegal character in URLs.
      if (preg_match("^$blob^", $uri)) {
        $url_settings['presigned_url'] = TRUE;
        $url_settings['timeout'] = $timeout;
        break;
      }
    }
    // Forced Save As.
    foreach ($this->saveas as $blob) {
      if (preg_match("^$blob^", $uri)) {
        $filename = basename($uri);
        $url_settings['api_args']['ResponseContentDisposition'] = "attachment; filename=\"$filename\"";
        $url_settings['forced_saveas'] = TRUE;
        break;
      }
    }


    // If a root folder has been set, prepend it to the $s3_key at this time.
    if (!empty($this->config['root_folder'])) {
      $s3_key = "{$this->config['root_folder']}/$s3_key";
    }

    if (empty($this->config['use_cname'])) {
      // We're not using a CNAME, so we ask S3 for the URL.
      $expires = NULL;
      if ($url_settings['presigned_url']) {
        $expires = "+{$url_settings['timeout']} seconds";
      }
      else {
        // Due to Amazon's security policies (see Request client
        // eters section @
        // http://docs.aws.amazon.com/AmazonS3/latest/API/RESTObjectGET.html),
        // only signed requests can use request parameters.
        // Thus, we must provide an expiry time for any URLs which specify
        // Response* API args. Currently, this only includes "Forced Save As".
        foreach ($url_settings['api_args'] as $key => $arg) {
          if (strpos($key, 'Response') === 0) {
            $expires = "+10 years";
            break;
          }
        }
      }
      $external_url = $this->s3->getObjectUrl($this->config['bucket'], $s3_key, $expires, $url_settings['api_args']);
      if (!empty($this->config['presigned_urls'])) {
        foreach (explode(PHP_EOL, $this->config['presigned_urls']) as $line) {
          $blob = trim($line);
          if ($blob) {
            $presigned_url_parts = explode("|", $blob);
            if (preg_match("^$presigned_url_parts[1]^", $s3_key) && $expires) {
              $command = $this->s3->getCommand('GetObject', [
                'Bucket' => $this->config['bucket'],
                'Key' => $s3_key
              ]);
              $external_url = $this->s3->createPresignedRequest($command, $expires);
              $uri = $external_url->getUri();
              $external_url = $uri->__toString();
            }
          }
        }
      }

    }
    else {
      // We are using a CNAME, so we need to manually construct the URL.
      $external_url = "{$this->domain}/$s3_key";
    }

    // If this file is versioned, append the version number as a GET arg to
    // ensure that browser caches will be bypassed upon version changes.
    $meta = $this->_read_cache($this->uri);
    if (!empty($meta['version'])) {
      $external_url = $this->_append_get_arg($external_url, $meta['version']);
    }

    // Torrents can only be created for publicly-accessible files:
    // https://forums.aws.amazon.com/thread.jspa?threadID=140949
    // So Forced SaveAs and Presigned URLs cannot be served as torrents.
    if (!$url_settings['forced_saveas'] && !$url_settings['presigned_url']) {
      foreach ($this->torrents as $blob) {
        if (preg_match("^$blob^", $uri)) {
          // You get a torrent URL by adding a "torrent" GET arg.
          $external_url = $this->_append_get_arg($external_url, 'torrent');
          break;
        }
      }
    }

    // If another module added a 'custom_GET_args' array to the url settings, process it here.
    if (!empty($url_settings['custom_GET_args'])) {
      foreach ($url_settings['custom_GET_args'] as $name => $value) {
        $external_url = $this->_append_get_arg($external_url, $name, $value);
      }
    }
    return $external_url;
  }

  /**
   * Support for fopen(), file_get_contents(), file_put_contents() etc.
   *
   * @param string $uri
   *   The URI of the file to open.
   * @param string $mode
   *   The file mode. Only 'r', 'w', 'a', and 'x' are supported.
   * @param int $options
   *   A bit mask of STREAM_USE_PATH and STREAM_REPORT_ERRORS.
   * @param string $opened_path
   *   An OUT parameter populated with the path which was opened.
   *   This wrapper does not support this parameter.
   *
   * @return bool
   *   TRUE if file was opened successfully. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-open.php
   */
  public function stream_open($uri, $mode, $options, &$opened_path) {
    $this->_debug("stream_open($uri, $mode, $options, $opened_path) called.");

    $this->uri = $uri;
    // We don't care about the binary flag, so strip it out.
    $this->access_mode = $mode = rtrim($mode, 'bt');
    $this->params = $this->_get_params($uri);
    $errors = array();

    if (strpos($mode, '+')) {
      $errors[] = t('The S3 File System stream wrapper does not allow simultaneous reading and writing.');
    }
    if (!in_array($mode, array('r', 'w', 'a', 'x'))) {
      $errors[] = t("Mode not supported: %mode. Use one 'r', 'w', 'a', or 'x'.", array('%mode' => $mode));
    }
    // When using mode "x", validate if the file exists first.
    if ($mode == 'x' && $this->_read_cache($uri)) {
      $errors[] = t("%uri already exists in your S3 bucket, so it cannot be opened with mode 'x'.", array('%uri' => $uri));
    }

    if (!$errors) {
      if ($mode == 'r') {
        $this->_open_read_stream($this->params, $errors);
      }
      else {
        if ($mode == 'a') {
          $this->_open_append_stream($this->params, $errors);
        }
        else {
          $this->_open_write_stream($this->params, $errors);
        }
      }
    }

    return $errors ? $this->_trigger_error($errors) : TRUE;
  }

  /**
   * This wrapper does not support flock().
   *
   * @return bool
   *   Always Returns FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-lock.php
   */
  public function stream_lock($operation) {
    $this->_debug("stream_lock($operation) called. S3fsStreamWrapper doesn't support this function.");

    return FALSE;
  }

  /**
   * Support for fread(), file_get_contents() etc.
   *
   * @param int $count
   *   Maximum number of bytes to be read.
   *
   * @return string
   *   The data which was read from the stream, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-read.php
   */
  public function stream_read($count) {
    $this->_debug("stream_read($count) called for {$this->uri}.");

    return $this->body->read($count);
  }

  /**
   * Support for fwrite(), file_put_contents() etc.
   *
   * @param string $data
   *   The data to be written to the stream.
   *
   * @return int
   *   The number of bytes actually written to the stream.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-write.php
   */
  public function stream_write($data) {
    $bytes = strlen($data);
    $this->_debug("stream_write() called with $bytes bytes of data for {$this->uri}.");

    return $this->body->write($data);
  }

  /**
   * Support for feof().
   *
   * @return bool
   *   TRUE if end-of-file has been reached. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-eof.php
   */
  public function stream_eof() {
    $this->_debug("stream_eof() called for {$this->uri}.");

    return $this->body->eof();
  }

  /**
   * Support for fseek().
   *
   * @param int $offset
   *   The byte offset to got to.
   * @param int $whence
   *   SEEK_SET, SEEK_CUR, or SEEK_END.
   *
   * @return bool
   *   TRUE on success. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-seek.php
   */
  public function stream_seek($offset, $whence = SEEK_SET) {
    $this->_debug("stream_seek($offset, $whence) called.");
    return $this->body->seek($offset, $whence);
  }

  /**
   * Support for fflush(). Flush current cached stream data to a file in S3.
   *
   * @return bool
   *   TRUE if data was successfully stored in S3.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-flush.php
   */
  public function stream_flush() {
    $this->_debug("stream_flush() called for {$this->uri}.");
    if ($this->access_mode == 'r') {
      return FALSE;
    }
    if ($this->body->isSeekable()) {
      $this->body->seek(0);
    }

    $params = $this->params;
    $params['Body'] = $this->body;
    $params['ContentType'] = \Drupal::service('file.mime_type.guesser')
      ->guess($params['Key']);
    if (!empty($this->config['saveas'])) {
      foreach (explode(PHP_EOL, $this->config['saveas']) as $line) {
        $blob = trim($line);
        if ($blob && preg_match("^$blob^", $this->uri)) {
          $params['ContentType'] = 'application/zip';
        }
      }
    }

    if (file_uri_scheme($this->uri) != 'private') {
      // All non-private files uploaded to S3 must be set to public-read, or users' browsers
      // will get PermissionDenied errors, and torrent URLs won't work.
      $params['ACL'] = 'public-read';
    }
    // Set the Cache-Control header, if the user specified one.
    if (!empty($this->config['cache_control_header'])) {
      $params['CacheControl'] = $this->config['cache_control_header'];
    }

    if (!empty($this->config['encryption'])) {
      $params['ServerSideEncryption'] = $this->config['encryption'];
    }

    try {
      $this->s3->putObject($params);
      $this->writeUriToCache($this->uri);
    } catch (\Exception $e) {
      $this->_debug($e->getMessage());
      return $this->_trigger_error($e->getMessage());
    }
    return TRUE;
  }

  /**
   * Support for ftell().
   *
   * @return int
   *   The current offset in bytes from the beginning of file.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-tell.php
   */
  public function stream_tell() {
    $this->_debug("stream_tell() called.");

    return $this->body->ftell();
  }


  /**
   * Support for fstat().
   *
   * @return array
   *   An array with file status, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  public function stream_stat() {
    $this->_debug("stream_stat() called for {$this->uri}.");
    $stat = fstat($this->body->getStream());
    // Add the size of the underlying stream if it is known.
    if ($this->access_mode == 'r' && $this->body->getSize()) {
      $stat[7] = $stat['size'] = $this->body->getSize();
    }

    return $stat;
  }

  /**
   * Support for fclose().
   *
   * Clears the object buffer.
   *
   * @return bool
   *   Always returns TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-close.php
   */
  public function stream_close() {
    $this->_debug("stream_close() called for {$this->uri}.");

    $this->body = NULL;
    $this->params = NULL;
    return $this->_error_state;
  }


  /**
   * Cast the stream to return the underlying file resource
   *
   * @param int $cast_as
   *   STREAM_CAST_FOR_SELECT or STREAM_CAST_AS_STREAM
   *
   * @return resource
   */
  public function stream_cast($cast_as) {
    $this->_debug("stream_cast($cast_as) called.");

    return $this->body->getStream();
  }

  //@Todo: Need Work??
  /**
   * {@inheritdoc}
   */
  public function stream_metadata($uri, $option, $value) {
    $this->_debug("stream_metadata called for {$this->uri}. S3fsStream does not support this function.");
    return TRUE;
  }


  /**
   * {@inheritdoc}
   *
   * Since Windows systems do not allow it and it is not needed for most use
   * cases anyway, this method is not supported on local files and will trigger
   * an error and return false. If needed, custom subclasses can provide
   * OS-specific implementations for advanced use cases.
   */
  public function stream_set_option($option, $arg1, $arg2) {
    trigger_error('stream_set_option() not supported for local file based stream wrappers', E_USER_WARNING);
    return FALSE;
  }

  //@todo: Needs Work
  /**
   * {@inheritdoc}
   */
  public function stream_truncate($new_size) {
    return ftruncate($this->handle, $new_size);
  }

  /**
   * Support for unlink().
   *
   * @param string $uri
   *   The uri of the resource to delete.
   *
   * @return bool
   *   TRUE if resource was successfully deleted, regardless of whether or not
   *   the file actually existed.
   *   FALSE if the call to S3 failed, in which case the file will not be
   *   removed from the cache.
   *
   * @see http://php.net/manual/en/streamwrapper.unlink.php
   */
  public function unlink($uri) {
    $this->_assert_constructor_called();
    $this->_debug("unlink($uri) called.");

    try {
      $this->s3->deleteObject($this->_get_params($uri));
      $this->_delete_cache($uri);
      clearstatcache(TRUE, $uri);
    } catch (\Exception $e) {
      $this->_debug($e->getMessage());
      return $this->_trigger_error($e->getMessage());
    }
    return TRUE;
  }

  /**
   * Support for rename().
   *
   * If $to_uri exists, this file will be overwritten. This behavior is
   * identical to the PHP rename() function.
   *
   * @param string $from_uri
   *   The uri of the file to be renamed.
   * @param string $to_uri
   *   The new uri for the file.
   *
   * @return bool
   *   TRUE if file was successfully renamed. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.rename.php
   */
  public function rename($from_uri, $to_uri) {
    $this->_assert_constructor_called();
    $this->_debug("rename($from_uri, $to_uri) called.");

    $from_params = $this->_get_params($from_uri);
    $to_params = $this->_get_params($to_uri);
    clearstatcache(TRUE, $from_uri);
    clearstatcache(TRUE, $to_uri);

    // Add the copyObject() parameters.
    $to_params['CopySource'] = "/{$from_params['Bucket']}/" . rawurlencode($from_params['Key']);
    $to_params['MetadataDirective'] = 'COPY';
    if (file_uri_scheme($from_uri) != 'private') {
      $to_params['ACL'] = 'public-read';
    }

    try {
      // Copy the original object to the specified destination.
      $this->s3->copyObject($to_params);
      // Copy the original object's metadata.
      $metadata = $this->_read_cache($from_uri);
      $metadata['uri'] = $to_uri;
      $this->_write_cache($metadata);
      $this->waitUntilFileExists($to_uri);
      // Now that we know the new object is there, delete the old one.
      return $this->unlink($from_uri);
    } catch (\Exception $e) {
      $this->_debug($e->getMessage());
      return $this->_trigger_error($e->getMessage());
    }
  }

  /**
   * Gets the name of the parent directory of a given path.
   *
   * This method is usually accessed through drupal_dirname(), which wraps
   * around the normal PHP dirname() function, since it doesn't support stream
   * wrappers.
   *
   * @param string $uri
   *   An optional URI.
   *
   * @return string
   *   The directory name, or FALSE if not applicable.
   *
   * @see drupal_dirname()
   */
  public function dirname($uri = NULL) {
    //   $this->_debug("dirname($uri) called.");

    if (!isset($uri)) {
      $uri = $this->uri;
    }
    $scheme = file_uri_scheme($uri);
    $dirname = dirname(file_uri_target($uri));

    // When the dirname() call above is given '$scheme://', it returns '.'.
    // But '$scheme://.' is an invalid uri, so we return "$scheme://" instead.
    if ($dirname == '.') {
      $dirname = '';
    }

    return "$scheme://$dirname";
  }

  /**
   * Support for mkdir().
   *
   * @param string $uri
   *   The URI to the directory to create.
   * @param int $mode
   *   Permission flags - see mkdir().
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS and STREAM_MKDIR_RECURSIVE.
   *
   * @return bool
   *   TRUE if the directory was successfully created. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.mkdir.php
   */
  public function mkdir($uri, $mode, $options) {
    $this->_assert_constructor_called();
    $this->_debug("mkdir($uri, $mode, $options) called.");

    clearstatcache(TRUE, $uri);
    // If this URI already exists in the cache, return TRUE if it's a folder
    // (so that recursive calls won't improperly report failure when they
    // reach an existing ancestor), or FALSE if it's a file (failure).
    $test_metadata = $this->_read_cache($uri);
    if ($test_metadata) {
      return (bool) $test_metadata['dir'];
    }

    $metadata = _s3fs_convert_metadata($uri, array());
    $this->_write_cache($metadata);

    // If the STREAM_MKDIR_RECURSIVE option was specified, also create all the
    // ancestor folders of this uri, except for the root directory.
    $parent_dir = drupal_dirname($uri);
    if (($options & STREAM_MKDIR_RECURSIVE) && file_uri_target($parent_dir) != '') {
      return $this->mkdir($parent_dir, $mode, $options);
    }
    return TRUE;
  }

  /**
   * Support for rmdir().
   *
   * @param string $uri
   *   The URI to the folder to delete.
   * @param int $options
   *   A bit mask of STREAM_REPORT_ERRORS.
   *
   * @return bool
   *   TRUE if folder is successfully removed.
   *   FALSE if $uri isn't a folder, or the folder is not empty.
   *
   * @see http://php.net/manual/en/streamwrapper.rmdir.php
   */
  public function rmdir($uri, $options) {
    $this->_assert_constructor_called();
    //  $this->_debug("rmdir($uri, $options) called.");

    if (!$this->_uri_is_dir($uri)) {
      return FALSE;
    }
  }

  /**
   * Support for stat().
   *
   * @param string $uri
   *   The URI to get information about.
   * @param int $flags
   *   A bit mask of STREAM_URL_STAT_LINK and STREAM_URL_STAT_QUIET.
   *   S3fsStreamWrapper ignores this value.
   *
   * @return array
   *   An array with file status, or FALSE in case of an error.
   *
   * @see http://php.net/manual/en/streamwrapper.url-stat.php
   */
  public function url_stat($uri, $flags) {

    $this->_assert_constructor_called();
    $this->_debug("url_stat($uri, $flags) called.");

    return $this->_stat($uri);
  }

  /**
   * Support for opendir().
   *
   * @param string $uri
   *   The URI to the directory to open.
   * @param int $options
   *   A flag used to enable safe_mode.
   *   This wrapper doesn't support safe_mode, so this parameter is ignored.
   *
   * @return bool
   *   TRUE on success. Otherwise, FALSE.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-opendir.php
   */
  public function dir_opendir($uri, $options = NULL) {
    $this->_assert_constructor_called();
    $this->_debug("dir_opendir($uri, $options) called.");

    if (!$this->_uri_is_dir($uri)) {
      return FALSE;
    }

    $scheme = file_uri_scheme($uri);
    $bare_uri = rtrim($uri, '/');
    $slash_uri = $bare_uri . '/';

    // If this URI was originally a root folder (e.g. s3://), the above code
    // removed *both* slashes but only added one back. So we need to add
    // back the second slash.
    if ($slash_uri == "$scheme:/") {
      $slash_uri = "$scheme://";
    }

    // Get the list of uris for files and folders which are children of the
    // specified folder, but not grandchildren.
    $child_uris = \Drupal::database()->select('s3fs_file', 's')
      ->fields('s', array('uri'))
      ->condition('uri', db_like($slash_uri) . '%', 'LIKE')
      ->condition('uri', db_like($slash_uri) . '%/%', 'NOT LIKE')
      ->execute()
      ->fetchCol(0);

    $this->dir = array();
    foreach ($child_uris as $child_uri) {
      $this->dir[] = basename($child_uri);
    }
    return TRUE;
  }

  /**
   * Support for readdir().
   *
   * @return string
   *   The next filename, or FALSE if there are no more files in the directory.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-readdir.php
   */
  public function dir_readdir() {
    $this->_debug("dir_readdir() called.");

    $entry = each($this->dir);
    return $entry ? $entry['value'] : FALSE;
  }

  /**
   * Support for rewinddir().
   *
   * @return bool
   *   Always returns TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-rewinddir.php
   */
  public function dir_rewinddir() {
    $this->_debug("dir_rewinddir() called.");

    reset($this->dir);
    return TRUE;
  }

  /**
   * Support for closedir().
   *
   * @return bool
   *   Always returns TRUE.
   *
   * @see http://php.net/manual/en/streamwrapper.dir-closedir.php
   */
  public function dir_closedir() {
    $this->_debug("dir_closedir() called.");

    unset($this->dir);
    return TRUE;
  }
  /***************************************************************************
   * Public Functions for External Use of the Wrapper
   ***************************************************************************/

  /**
   * Wait for the specified file to exist in the bucket.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return bool
   *   Returns TRUE once the waiting finishes, or FALSE if the file does not
   *   begin to exist within 10 seconds.
   */
  public function waitUntilFileExists($uri) {
    $params = $this->_get_params($uri);
    try {
      $this->s3->waitUntil('ObjectExists', $params);
    } catch (Aws\Common\Exception\RuntimeException $e) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Write the file at the given URI into the metadata cache.
   *
   * This function is public so that other code can upload files to S3 and
   * then have us write the correct metadata into our cache.
   */
  public function writeUriToCache($uri) {
    if (!$this->waitUntilFileExists($uri)) {
      throw new S3fsException(t('The file at URI %file does not exist in S3.', array('%file' => $uri)));
    }
    $metadata = $this->_get_metadata_from_s3($uri);
    $this->_write_cache($metadata);
    clearstatcache(TRUE, $uri);
  }
  /***************************************************************************
   * Internal Functions
   ***************************************************************************/

  /**
   * Get the status of the file with the specified URI.
   *
   * @return array
   *   An array with file status, or FALSE if the file doesn't exist.
   *
   * @see http://php.net/manual/en/streamwrapper.stream-stat.php
   */
  protected function _stat($uri) {
    $this->_debug("_stat($uri) called.", TRUE);

    $metadata = $this->_s3fs_get_object($uri);
    if ($metadata) {
      $stat = array();
      $stat[0] = $stat['dev'] = 0;
      $stat[1] = $stat['ino'] = 0;
      // Use the S_IFDIR posix flag for directories, S_IFREG for files.
      // All files are considered writable, so OR in 0777.
      $stat[2] = $stat['mode'] = ($metadata['dir'] ? 0040000 : 0100000) | 0777;
      $stat[3] = $stat['nlink'] = 0;
      $stat[4] = $stat['uid'] = 0;
      $stat[5] = $stat['gid'] = 0;
      $stat[6] = $stat['rdev'] = 0;
      $stat[7] = $stat['size'] = 0;
      $stat[8] = $stat['atime'] = 0;
      $stat[9] = $stat['mtime'] = 0;
      $stat[10] = $stat['ctime'] = 0;
      $stat[11] = $stat['blksize'] = 0;
      $stat[12] = $stat['blocks'] = 0;

      if (!$metadata['dir']) {
        $stat[4] = $stat['uid'] = 's3fs';
        $stat[7] = $stat['size'] = $metadata['filesize'];
        $stat[8] = $stat['atime'] = $metadata['timestamp'];
        $stat[9] = $stat['mtime'] = $metadata['timestamp'];
        $stat[10] = $stat['ctime'] = $metadata['timestamp'];
      }
      return $stat;
    }
    return FALSE;
  }

  /**
   * Determine whether the $uri is a directory.
   *
   * @param string $uri
   *   The uri of the resource to check.
   *
   * @return bool
   *   TRUE if the resource is a directory.
   */
  protected function _uri_is_dir($uri) {
    $metadata = $this->_s3fs_get_object($uri);
    return $metadata ? $metadata['dir'] : FALSE;
  }

  /**
   * Try to fetch an object from the metadata cache.
   *
   * If that file isn't in the cache, we assume it doesn't exist.
   *
   * @param string $uri
   *   The uri of the resource to check.
   *
   * @return bool
   *   An array if the $uri exists, otherwise FALSE.
   */
  protected function _s3fs_get_object($uri) {
    $this->_debug("_s3fs_get_object($uri) called.", TRUE);

    // For the root directory, return metadata for a generic folder.
    if (file_uri_target($uri) == '') {
      return _s3fs_convert_metadata('/', array());
    }

    // Trim any trailing '/', in case this is a folder request.
    $uri = rtrim($uri, '/');

    // Check if this URI is in the cache.
    $metadata = $this->_read_cache($uri);

    // If cache ignore is enabled, query S3 for all URIs which aren't in the
    // cache, and non-folder URIs which are.
    if (!empty($this->config['ignore_cache']) && !$metadata['dir']) {
      try {
        // If _get_metadata_from_s3() returns FALSE, the file doesn't exist.
        $metadata = $this->_get_metadata_from_s3($uri);
      } catch (\Exception $e) {
        $this->_debug($e->getMessage());
        return $this->_trigger_error($e->getMessage());
      }
    }
    return $metadata;
  }

  /**
   * Fetch an object from the file metadata cache table.
   *
   * @param string $uri
   *   The uri of the resource to check.
   *
   * @return array
   *   An array of metadata if the $uri is in the cache. Otherwise, FALSE.
   */
  protected function _read_cache($uri) {
    $this->_debug("_read_cache($uri) called.", TRUE);

    // Since public:///blah.jpg and public://blah.jpg refer to the same file
    // (a file named blah.jpg at the root of the file system), we'll sometimes
    // receive files with a /// in their URI. This messes with our caching
    // scheme, though, so we need to remove the extra /.
    if (strpos($uri, 'public:///') === 0) {
      $uri = preg_replace('^public://[/]+^', 'public://', $uri);
    }
    else {
      if (strpos($uri, 'private:///') === 0) {
        $uri = preg_replace('^private://[/]+^', 'private://', $uri);
      }
    }
    //@todo: Cache Implementation

    $record = \Drupal::database()->select('s3fs_file', 's')
      ->fields('s')
      ->condition('uri', $uri, '=')
      ->execute()
      ->fetchAssoc();
    return $record ? $record : FALSE;
  }

  /**
   * Write an object's (and its ancestor folders') metadata to the cache.
   *
   * @param array $metadata
   *   An associative array of file metadata in this format:
   *     'uri' => The full URI of the file, including the scheme.
   *     'filesize' => The size of the file, in bytes.
   *     'timestamp' => The file's create/update timestamp.
   *     'dir' => A boolean indicating whether the object is a directory.
   *
   * @throws
   *   Exceptions which occur in the database call will percolate.
   */
  protected function _write_cache($metadata) {
    $this->_debug("_write_cache({$metadata['uri']}) called.", TRUE);

    // Since public:///blah.jpg and public://blah.jpg refer to the same file
    // (a file named blah.jpg at the root of the file system), we'll sometimes
    // receive files with a /// in their URI. This messes with our caching
    // scheme, though, so we need to remove the extra /.
    //@todo: Work this out if needed
    /*if (strpos($metadata['uri'], 'public:///') === 0) {
      $metadata['uri'] = preg_replace('^public://[/]+^', 'public://', $metadata['uri']);
    }
    else if (strpos($metadata['uri'], 'private:///') === 0) {
      $metadata['uri'] = preg_replace('^private://[/]+^', 'private://', $metadata['uri']);
    }*/

    db_merge('s3fs_file')
      ->key(array('uri' => $metadata['uri']))
      ->fields($metadata)
      ->execute();

    // Clear this URI from the Drupal cache, to ensure the next read isn't
    // from a stale cache entry.
//    $cid = S3FS_CACHE_PREFIX . $metadata['uri'];
//    $cache = \Drupal::cache('S3FS_CACHE_BIN');
//    $cache->delete($cid);

    $dirname = drupal_dirname($metadata['uri']);
    // If this file isn't in the root directory, also write this file's
    // ancestor folders to the cache.
    if (file_uri_target($dirname) != '') {
      $this->mkdir($dirname, NULL, STREAM_MKDIR_RECURSIVE);
    }
  }

  /**
   * Delete an object's metadata from the cache.
   *
   * @param mixed $uri
   *   A string (or array of strings) containing the URI(s) of the object(s)
   *   to be deleted.
   *
   * @throws
   *   Exceptions which occur in the database call will percolate.
   */
  protected function _delete_cache($uri) {
    $this->_debug("_delete_cache($uri) called.", TRUE);

    if (!is_array($uri)) {
      $uri = array($uri);
    }

    // Build an OR query to delete all the URIs at once.
    $delete_query = db_delete('s3fs_file');
    $or = db_or();
    foreach ($uri as $u) {
      $or->condition('uri', $u, '=');
      // Clear this URI from the Drupal cache.
//      $cid = S3FS_CACHE_PREFIX . $u;
//      $cache = \Drupal::cache('S3FS_CACHE_BIN');
//      $cache->delete($cid);
    }
    $delete_query->condition($or);
    return $delete_query->execute();
  }

  /**
   * Get the stream context options available to the current stream.
   *
   * @return array
   */
  protected function _get_options() {
    $context = isset($this->context) ? $this->context : stream_context_get_default();
    $options = stream_context_get_options($context);
    return isset($options['s3']) ? $options['s3'] : array();
  }

  /**
   * Get a specific stream context option.
   *
   * @param string $name
   *   Name of the option to retrieve.
   *
   * @return mixed|null
   */
  protected function _get_option($name) {
    $options = $this->_get_options();
    return isset($options[$name]) ? $options[$name] : NULL;
  }

  /**
   * Get the Command parameters for the specified URI.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return array
   *   A Command parameters array, including 'Bucket', 'Key', and
   *   context parameters.
   */
  protected function _get_params($uri) {
    $params = $this->_get_options();
    unset($params['seekable']);
    unset($params['throw_exceptions']);

    $params['Bucket'] = $this->config['bucket'];
    $params['Key'] = file_uri_target($uri);

    // public:// file are all placed in the s3fs-public/ folder.
    if (file_uri_scheme($uri) == 'public') {
      $params['Key'] = "s3fs-public/{$params['Key']}";
    }
    // private:// file are all placed in the s3fs-private/ folder.
    else {
      if (file_uri_scheme($uri) == 'private') {
        $params['Key'] = "s3fs-private/{$params['Key']}";
      }
    }

    // If it's set, all files are placed in the root folder.
    if (!empty($this->config['root_folder'])) {
      $params['Key'] = "{$this->config['root_folder']}/{$params['Key']}";
    }
    return $params;
  }

  /**
   * Initialize the stream wrapper for a read only stream.
   *
   * @param array $params
   *   An array of AWS SDK for PHP Command parameters.
   * @param array $errors
   *   Array to which encountered errors should be appended.
   */
  protected function _open_read_stream($params, &$errors) {
    $this->_debug("_open_read_stream({$params['Key']}) called.", TRUE);
    $client = $this->s3;
    $command = $client->getCommand('GetObject', $params);
    $command['@http']['stream'] = TRUE;
    $result = $client->execute($command);
    $this->size = $result['ContentLength'];
    $this->body = $result['Body'];
    // Wrap the body in a caching entity body if seeking is allowed
    //if ($params('seekable') && !$this->body->isSeekable()) {
    $this->body = new CachingStream($this->body);
    // }
    return TRUE;
  }

  /**
   * Initialize the stream wrapper for an append stream.
   *
   * @param array $params
   *   An array of AWS SDK for PHP Command parameters.
   * @param array $errors
   *   OUT parameter: all encountered errors are appended to this array.
   */
  protected function _open_append_stream($params, &$errors) {
    $this->_debug("_open_append_stream({$params['Key']}) called.", TRUE);

    try {
      // Get the body of the object
      $this->body = $this->s3->getObject($params)->get('Body');
      $this->body->seek(0, SEEK_END);
    } catch (Aws\S3\Exception\S3Exception $e) {
      // The object does not exist, so use a simple write stream.
      $this->_open_write_stream($params, $errors);
    }
  }

  /**
   * Initialize the stream wrapper for a write only stream.
   *
   * @param array $params
   *   An array of AWS SDK for PHP Command parameters.
   * @param array $errors
   *   OUT parameter: all encountered errors are appended to this array.
   */
  protected function _open_write_stream($params, &$errors) {
    $this->_debug("_open_write_stream({$params['Key']}) called.", TRUE);
    $this->body = new Stream(fopen('php://temp', 'r+'));
    return TRUE;

  }

  /**
   * Serialize and sign a command, returning a request object.
   *
   * @param CommandInterface $command
   *   The Command to sign.
   *
   * @return RequestInterface
   */
  protected function _get_signed_request($command) {
    $this->_debug("_get_signed_request() called.", TRUE);

    $request = $command->prepare();
    $request->dispatch('request.before_send', array('request' => $request));
    return $request;
  }

  /**
   * Returns the converted metadata for an object in S3.
   *
   * @param string $uri
   *   The URI for the object in S3.
   *
   * @return array
   *   An array of DB-compatible file metadata.
   *
   * @throws \Exception
   *   Any exception raised by the listObjects() S3 command will percolate
   *   out of this function.
   */
  protected function _get_metadata_from_s3($uri) {
    $this->_debug("_get_metadata_from_s3($uri) called.", TRUE);
    $params = $this->_get_params($uri);
    try {
      $result = $this->s3->headObject($params);
    } catch (Aws\S3\Exception\NoSuchKeyException $e) {
      // headObject() throws this exception if the requested key doesn't exist
      // in the bucket.
      return FALSE;
    }

    return _s3fs_convert_metadata($uri, $result);
  }

  /**
   * Triggers one or more errors.
   *
   * @param string|array $errors
   *   Errors to trigger.
   * @param mixed $flags
   *   If set to STREAM_URL_STAT_QUIET, no error or exception is triggered.
   *
   * @return bool
   *   Always returns FALSE.
   *
   * @throws RuntimeException
   *   If the 'throw_exceptions' option is TRUE.
   */
  protected function _trigger_error($errors, $flags = NULL) {
    if ($flags != STREAM_URL_STAT_QUIET) {
      if ($this->_get_option('throw_exceptions')) {
        throw new RuntimeException(implode("\n", (array) $errors));
      }
      else {
        trigger_error(implode("\n", (array) $errors), E_USER_ERROR);
      }
    }
    $this->_error_state = TRUE;
    return FALSE;
  }

  /**
   * Call the constructor it it hasn't been called yet.
   *
   * Due to PHP bug #40459, the constructor of this class isn't always called
   * for some of the methods.
   *
   * @see https://bugs.php.net/bug.php?id=40459
   */
  protected function _assert_constructor_called() {
    if (!$this->constructed) {
      $this->__construct();
    }
  }

  /**
   * Logging function used for debugging.
   *
   * This function only writes anything if the global variable $_s3fs_debug
   * is TRUE.
   *
   * @param string $msg
   *   The debug message to log.
   * @param bool $internal
   *   If this is TRUE, don't log $msg unless $_s3fs_debug_internal is TRUE.
   */
  protected static function _debug($msg, $internal = FALSE) {
    global $_s3fs_debug, $_s3fs_debug_internal;
    if ($_s3fs_debug && (!$internal || $_s3fs_debug_internal)) {
      debug($msg);
    }
  }

  /**
   * Helper function to safely append a GET argument to a given base URL.
   *
   * @param string $base_url
   *   The URL onto which the GET arg will be appended.
   * @param string $name
   *   The name of the GET argument.
   * @param string $value
   *   The value of the GET argument. Optional.
   */
  protected static function _append_get_arg($base_url, $name, $value = NULL) {
    $separator = strpos($base_url, '?') === FALSE ? '?' : '&';
    $new_url = "{$base_url}{$separator}{$name}";
    if ($value !== NULL) {
      $new_url .= "=$value";
    }
    return $new_url;
  }

  protected function getTarget($uri = NULL) {
    if (!isset($uri)) {
      $uri = $this->uri;
    }

    list(, $target) = explode('://', $uri, 2);

    // Remove erroneous leading or trailing, forward-slashes and backslashes.
    return trim($target, '\/');
  }
}

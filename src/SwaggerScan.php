<?php

namespace Drupal\swagger;

use Swagger\Analysis;
use \JsonSerializable;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

//use Drupal\Core\Entity\EntityTypeManagerInterface;
//use Drupal\rest\Plugin\Type\ResourcePluginManager;


/**
 * Provides a SwaggerScan plugin implementation.
 */
class SwaggerScan implements SwaggerScanInterface {

  /**
   * ConfigFactory swagger.settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;
  
  private $swaggerInfoAnnotation;
  
  private $swaggerRootAnnotation;
  
  private $swaggerAnalysis;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config, LoggerChannelInterface $logger, JsonSerializable $info_annotation, JsonSerializable $root_annotation, Analysis $analysis) {
    $this->config = $config->getEditable('swagger.settings');
    $this->logger = $logger;
    $this->swaggerInfoAnnotation = $info_annotation;
    $this->swaggerRootAnnotation = $root_annotation;
    $this->swaggerAnalysis = $analysis; 
  }

  /**
   * {@inheritdoc}
   */
  public function generateSwaggerFile($base_url) {
    // Get drupal configurations.
    $scan_folder = './' . $this->config->get('swagger_scan_folder');
    $file_path = './' . $this->config->get('swagger_scan_output');
    $json_file = $file_path . '/swagger.json';
    // Prepare directory file.
    if (!file_prepare_directory($file_path, FILE_CREATE_DIRECTORY)) {
      $message = t("You don't have permission in the directory:") . ' ' . $file_path;
      drupal_set_message($message, 'status');
      $this->logger->error($message);
      exit();
    }
    // Prepare the basic structure.
    $analysis = $this->swaggerPrepareBase($base_url);
    $swagger = \Swagger\scan($scan_folder, array('analysis' => $analysis));
    // Output report.
    $this->swaggerOutputReport($swagger);
    // Save fix.
    if (file_put_contents($json_file, $swagger)) {
      drupal_set_message(t('Written to:') . ' ' . realpath($json_file), 'status');
    }
  }

  /**
   * Function swaggerPrepareBase()
   *
   * @return object
   *   $analysis new Analysis().
   */
  protected function swaggerPrepareBase($base_url) {
    // Checking the swagger format files.
    $this->swaggerCheckJsonFormat();
    $this->swaggerAnalysis->swagger->basePath = \Drupal::request()->getBasePath();
    $this->swaggerAnalysis->swagger->host = preg_replace('/^http(s)?:\/\//i', '', $base_url);
    //$this->swaggerAnalysis->validate();
    return $this->swaggerAnalysis;
  }

  /**
   * Function swaggerOutputReport()
   */
  protected function swaggerOutputReport($swagger) {
    $methods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'];
    $counter = 0;
    drupal_set_message(t('----  SWAGGER SCANED ---'));
    // Output report.
    foreach ($swagger->paths as $path) {
      foreach ($path as $method => $operation) {
        if ($operation !== NULL && in_array($method, $methods)) {
          $output_swagger_message = str_pad($method, 7, ' ', STR_PAD_LEFT) . ' ' . $path->path;
          drupal_set_message($output_swagger_message);
          $counter++;
        }
      }
    }
    drupal_set_message($counter . ' ' . t('OPERATIONS DOCUMENTED.'));
    if ($counter == 0) {
      drupal_set_message(t("Your code don't have annotations. Could you try to change the Scan folder path", 'warning'), 'warning');
    }
    return $swagger;
  }

  /**
   * Function swaggerCheckJsonFormat().
   *
   * Checking the swagger format files.
   */
  protected function swaggerCheckJsonFormat() {
    $errorTypes = [
      E_ERROR => 'ERROR',
      E_WARNING => 'WARNING',
      E_PARSE => 'PARSE',
      E_NOTICE => 'NOTICE',
      E_CORE_ERROR => 'CORE_ERROR',
      E_CORE_WARNING => 'CORE_WARNING',
      E_COMPILE_ERROR => 'COMPILE_ERROR',
      E_COMPILE_WARNING => 'COMPILE_WARNING',
      E_USER_ERROR => 'ERROR',
      E_USER_WARNING => 'WARNING',
      E_USER_NOTICE => 'NOTICE',
      E_STRICT => 'STRICT',
      E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
      E_DEPRECATED => 'DEPRECATED',
      E_USER_DEPRECATED => 'DEPRECATED',
    ];
    set_error_handler(function ($errno, $errstr, $file, $line) use ($errorTypes) {
      if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting.
        return;
      }
      $type = array_key_exists($errno, $errorTypes) ? $errorTypes[$errno] : 'ERROR';
      $this->logger->error('[' . $type . '] ' . $errstr . ' in ' . $file . ' on line ' . $line);
      if ($type === 'ERROR') {
        exit($errno);
      }
    });
    set_exception_handler(function ($exception) {
      $this->logger->error('[EXCEPTION] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
      exit($exception->getCode() ?: 1);
    });
    \Swagger\Logger::getInstance()->log = function ($entry, $type) {
      $type = $type === E_USER_NOTICE ? 'INFO' : 'WARN';
      if ($entry instanceof Exception) {
        $entry = $entry->getMessage();
      }
      drupal_set_message(t('---  SWAGGER WARNING ---'), 'error');
      drupal_set_message('[' . $type . '] ' . $entry . PHP_EOL, 'error');
    };
  }

}

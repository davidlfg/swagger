<?php

namespace Drupal\swagger;

use Drupal\Core\Url;
use Swagger\Analysis;
use Swagger\Annotations\Info;
use Swagger\Annotations\Swagger;
use Swagger\Processors\MergeIntoSwagger;

/**
 * Provides a SwaggerScan plugin implementation.
 */
class SwaggerScan implements SwaggerScanInterface {
  
  /**
   * {@inheritdoc}
   */
  public function generateSwaggerFile($base_url) {
    //get drupal configurations
    $config = \Drupal::config('swagger.settings');
    $scan_folder = './' . $config->get('swagger_scan_folder');
    $file_path = './' . $config->get('swagger_scan_output');
    $json_file = $file_path . '/swagger.json';
    //prepare directory file
    if (!file_prepare_directory($file_path, FILE_CREATE_DIRECTORY)) {
      $message = t("You don't have permission in the directory: ") . $file_path;
      drupal_set_message(t('Written to: '), 'status');
      \Drupal::logger('my_module')->error($message);
      exit();
    }
    //Prepare the basic structure  
    $analysis = $this->_swagger_prepare_base($base_url);
    $swagger = \Swagger\scan($scan_folder, array('analysis' => $analysis));
    // Output report
    $this->_swagger_output_report($swagger);
    //save fix
    if (file_put_contents($json_file, $swagger)) {
      drupal_set_message(t('Written to: ') . realpath($json_file), 'status');
    }
  }
  
  /**
   * Function _swagger_prepare_base()
   * 
   * @return $analysis new Analysis()
   */
  protected function _swagger_prepare_base($base_url) {
    //Checking the swagger format files
    $this->_swagger_check_json_format();
    //scan custom swagger processor
    $swagger_info_object = $this->_swagger_info_object();
    //Get object swagger
    $swagger = new Swagger($this->_swagger_swagger_object($swagger_info_object, $base_url));
    $analysis = new Analysis([$swagger]);
    $analysis->process([
      new MergeIntoSwagger()
    ]);
    $analysis->validate();
    return $analysis;
  }
  
  /**
   * Function _swagger_output_report()
   */
  protected function _swagger_output_report($swagger) {
    $methods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'];
    $counter = 0;
    drupal_set_message(t('----  SWAGGER SCANED ---'));
    // Output report
    foreach ($swagger->paths as $path) {
      foreach ($path as $method => $operation) {
        if ($operation !== null && in_array($method, $methods)) {
          $output_swagger_message = str_pad($method, 7, ' ', STR_PAD_LEFT) . ' ' . $path->path;
          drupal_set_message($output_swagger_message);
          $counter++;
        }
      }
    }
    drupal_set_message($counter . t(' OPERATIONS DOCUMENTED.'));
    if ($counter == 0) {
      drupal_set_message(t("Your code don't have annotations. Could you try to change the Scan folder path", 'warning'), 'warning');
    }
    return $swagger;
  }
  
  /**
   * Function _swagger_check_json_format().
   *  
   * Checking the swagger format files
   */
  protected function _swagger_check_json_format() {
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
      E_USER_DEPRECATED => 'DEPRECATED'
    ];
    set_error_handler(function ($errno, $errstr, $file, $line) use ($errorTypes) {
      if (!(error_reporting() & $errno)) {
        return; // This error code is not included in error_reporting
      }
      $type = array_key_exists($errno, $errorTypes) ? $errorTypes[$errno] : 'ERROR';
      \Drupal::logger('my_module')->error('[' . $type . '] '.$errstr .' in '.$file.' on line '.$line);
      if ($type === 'ERROR') {
        exit($errno);
      }
    });
    set_exception_handler(function ($exception) {
      \Drupal::logger('my_module')->error('[EXCEPTION] '.$exception->getMessage() .' in '.$exception->getFile().' on line '.$exception->getLine());
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
  
  /**
   * Function _swagger_info_object().
   * 
   * @return Info Object
   */
  protected function _swagger_info_object() {
    $config = \Drupal::config('swagger.settings');
    $info = [
      "title" => $config->get('swagger_info_title'),
      "description" => $config->get('swagger_info_description'),
      "termsOfService" => $config->get('swagger_info_terms_service'),
      "version" => $config->get('swagger_info_version')
    ];
    if ($config->get('contact_needs')) {
      $info["contact"] = [
        "name" => $config->get('swagger_info_contact_name'),
        "url" => $config->get('swagger_info_contact_url'),
        "email" => $config->get('swagger_info_contact_email'),
      ];
    }
    if ($config->get('license_needs')) {
      $info["license"] = [
        "name" => $config->get('swagger_info_license_name'),
        "url" => $config->get('swagger_info_license_url'),
      ];
    }
    return $info;
  }
  
  /**
   * Function _swagger_swagger_object().
   *
   * @return Info Object
   */
  protected function _swagger_swagger_object($swagger_info_object, $base_url) {
    $config = \Drupal::config('swagger.settings');
    return [
      'swagger' => $config->get('swagger_swagger_version'),
      'info' => new Info($swagger_info_object),
      'host' => preg_replace('/^http(s)?:\/\//i', '', $base_url),
      'basePath' => \Drupal::request()->getBasePath(),
      'schemes' => $config->get('swagger_swagger_schemes'),
      'consumes' => $config->get('swagger_swagger_consumes'),
      'produces' => $config->get('swagger_swagger_produces'),
    ];
  }
}
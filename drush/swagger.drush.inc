<?php

/**
 * @file
 * Drush integration for the swagger module.
 */

use Drupal\swagger\Controller;
use Drupal\Core\Url;
use Swagger\Analyser;
use Swagger\Context;
use Swagger\StaticAnalyser;
use Swagger\Annotations\Info;
use Swagger\Processors\InheritProperties;
use Swagger\Processors\MergeIntoSwagger;

/**
 * Implements hook_drush_command().
 */
function swagger_drush_command() {
  $items = array();
  $items['swagger-scan'] = [
    'description' => 'Run the scan code.',
    'arguments' => [
      'site_uri' => ''  
    ],
    'drupal dependencies' => ['swagger'],
    'aliases' => ['sws'],
  ];

  return $items;
}

/**
 * Command callback to run the scan code.
 */
function drush_swagger_scan($site_uri = NULL) {
  if (is_null($site_uri)) {
    drush_print('Enter site URI');
    drush_print('drush sws [site uri]');
    return;
  }
  //get drupal configurations
  $config = \Drupal::config('swagger.settings');
  $scan_folder = './' . $config->get('swagger_scan_folder');
  $file_path = './' . $config->get('swagger_scan_output');
  $json_file = $file_path . '/swagger.json';
  $swagger = _swagger_scan_and_prepare($scan_folder, $file_path, $site_uri);
  //save
  $is_write = file_put_contents($json_file, $swagger);
  drush_print('Written to '.realpath($json_file));
  drush_print('');
}

/**
 * Function _swagger_scan_and_prepare()
 */
function _swagger_scan_and_prepare($scan_folder, $file_path, $site_uri) {
  //prepare directory file
  if (!file_prepare_directory($file_path, FILE_CREATE_DIRECTORY)) {
    drush_print("You don't have permission in the directory: " . $file_path);
    exit(
      );
  }
  //Checking the swagger format files
  _swagger_check_json_format();
  //GET custom swagger processor
  $url = $site_uri . '/' . Url::fromRoute('swagger.swagger')->getInternalPath() . '?_format=json';
  $info_swagger = file_get_contents($url);
  $analyser = new StaticAnalyser();
  $analysis = $analyser->fromFile($file_path);
  $analysis->process([
      new MergeIntoSwagger()
  ]);
  $analysis->swagger->info = new Info(['title' => 'tmp', 'version' => '1.0']);
  $analysis->validate();
  $swagger = \Swagger\scan($scan_folder, array('analysis' => $analysis));
  
  $methods = ['get', 'put', 'post', 'delete', 'options', 'head', 'patch'];
  $counter = 0;

  // Output report
  foreach ($swagger->paths as $path) {
    foreach ($path as $method => $operation) {
      if ($operation !== null && in_array($method, $methods)) {
        drush_print(str_pad($method, 7, ' ', STR_PAD_LEFT) . ' ' . $path->path);
        $counter++;
      }
    }
  }
  drush_print('----------------------'.  str_repeat('-', strlen($counter)));
  drush_print($counter.' operations documented');
  drush_print('----------------------'.  str_repeat('-', strlen($counter)));
  
  //merge base swagger annotations and custom swagger annotations
  $swagger = json_encode(array_merge(json_decode($info_swagger, true),json_decode($swagger, true)), JSON_PRETTY_PRINT);
  return $swagger;
}

/**
 * Function _swagger_check_json_format().
 *  
 * Checking the swagger format files
 */
function _swagger_check_json_format() {
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
    drush_print('[' . $type . '] '.$errstr .' in '.$file.' on line '.$line);
    if ($type === 'ERROR') {
      exit($errno);
    }
  });
  set_exception_handler(function ($exception) {
    drush_print('[EXCEPTION] '.$exception->getMessage() .' in '.$exception->getFile().' on line '.$exception->getLine());
    exit($exception->getCode() ?: 1);
  });
  \Swagger\Logger::getInstance()->log = function ($entry, $type) {
    $type = $type === E_USER_NOTICE ? 'INFO' : 'WARN';
    if ($entry instanceof Exception) {
      $entry = $entry->getMessage();
    }
    drush_print('[' . $type . '] ' . $entry . PHP_EOL);
  };
}
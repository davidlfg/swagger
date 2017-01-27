<?php

namespace Drupal\swagger;

/**
 * Provides an interface defining a custom scan code.
 */
interface SwaggerScanInterface {

  /**
   * Execute the scan and save.
   *
   * @param string $base_url
   *   Necessary site uri by Drush command.
   */
  public function generateSwaggerFile($base_url);

}

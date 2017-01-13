<?php

namespace Drupal\swagger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;


/**
 * Controller for the Swagger UI page callbacks
 */
class SwaggerController extends ControllerBase {

  /**
   * The Swagger UI page.
   *
   * @return array
   */
  public function swaggerUiPage() {
    $config = $this->config('swagger.settings');
    require_once('core/includes/install.inc');//fix
    //verify if exist library swagger-ui and swagger.json file
    if (!drupal_verify_install_file('libraries/swagger-ui/dist/swagger-ui.jss', FILE_EXIST) && !drupal_verify_install_file($config->get('swagger_scan_output') . '/swagger.json', FILE_EXIST)) {
      drupal_set_message($this->t('Please contact the administrator.'), 'warning', FALSE);
      return [
        '#theme' => 'swagger_ui'
      ];
    }
    $path_swagger_json = 'http://' . \Drupal::request()->getHost() . '/' . $config->get('swagger_scan_output') . '/swagger.json';//fix request()->getHost()
    $build = [
      '#theme' => 'swagger_ui',
      '#attached' => [
        'library' => [
          'swagger/swagger_ui_integration',
          'swagger/swagger_ui',
        ],
        'drupalSettings' => [
          'swagger' => [
            'swagger_json_url' => $path_swagger_json,
          ],
        ],
      ],
    ];
    return $build;
  }
}

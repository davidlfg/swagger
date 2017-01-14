<?php

namespace Drupal\swagger\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for the Swagger UI page callbacks
 */
class SwaggerController extends ControllerBase {
  
  /**
   * Output Swagger compatible API spec.
   */
  public function swaggerAPI() {
    $spec = [
      'swagger' => "2.0",
      'schemes' => ['http'],
      'info' => $this->getInfo(),
      'host' => \Drupal::request()->getHost(),
      'basePath' => \Drupal::request()->getBasePath(),

    ];
    $response = new JsonResponse($spec);
    return $response;

  }

  /**
   * Creates the 'info' portion of the API.
   *
   * @return array
   *   The info elements.
   */
  protected function getInfo() {
    $site_name = $this->config('system.site')->get('name');
    return [
      'description' => 'Create configuration field',
      'title' => $this->t('@site - API', ['@site' => $site_name]),
    ];
  }


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
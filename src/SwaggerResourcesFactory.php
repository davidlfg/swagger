<?php

namespace Drupal\swagger;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\Routing\Route;
use Drupal\rest\Plugin\rest\resource\EntityResource;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Defines a factory for logging channels.
 */
class SwaggerResourcesFactory {

  /**
   * Entity type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  private $entity_type_manager;

  /**
   * Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $field_manager;

  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManagerInterface $field_manager) {
    $this->entity_type_manager = $entity_type_manager;
    $this->field_manager = $field_manager;
  }

  /**
   * Function swaggerPathsResourcesObject().
   *
   * @return array
   *   The swagger info to the swagger file.
   */
  public function swaggerPathsResourcesObject() {
    $paths = $this->getPaths();
    $resource_paths = [];
    foreach($paths as $path => $methods) {
      foreach($methods as $method => $data) {
        $data['path'] = $path;
        $data['summary'] = (string) $method . '-' . $path;
        $data['responses'] = [];
        $data['tags'] = ['REST resources'];
        switch ($method) {
          case "get":
            $resource_paths[] = new Annotations\Get($data);
          break;
        }
      }
    }
    return $resource_paths;
  }

  /**
   * Returns the paths information.
   *
   * @return array
   *   The info elements.
   */
  public function getPaths() {
    $api_paths = [];
    $resource_configs = $this->entity_type_manager->getStorage('rest_resource_config')->loadMultiple();

    foreach ($resource_configs as $id => $resource_config) {
      /** @var \Drupal\rest\Plugin\ResourceBase $plugin */
      $resource_plugin = $resource_config->getResourcePlugin();
      foreach ($resource_config->getMethods() as $method) {
        if ($route = $this->getRouteForResourceMethod($resource_config, $method)) {
          $swagger_method = strtolower($method);
          $path = $route->getPath();
          $path_method_spec = [];
          $formats = $resource_config->getFormats($method);
          $format_parameter = [
            'name' => '_format',
            'in' => 'query',
            'enum' => $formats,
            'required' => TRUE,
          ];
          if (count($formats) == 1) {
            $format_parameter['default'] = $formats[0];
          }
          $path_method_spec['parameters'][] = $format_parameter;
          if ($resource_plugin instanceof EntityResource) {

            $entity_type = $this->entity_type_manager->getDefinition($resource_plugin->getPluginDefinition()['entity_type']);
            $path_method_spec['summary'] = t('@method a @entity_type', [
              '@method' => ucfirst($swagger_method),
              '@entity_type' => $entity_type->getLabel(),
            ]);

            $path_method_spec['consumes'] = ['application/json'];
            $path_method_spec['produces'] = ['application/json'];
            $path_method_spec['parameters'] += $this->getEntityParameters($entity_type, $method);

          }
          else {
            $path_method_spec['summary'] = $resource_plugin->getPluginDefinition()['label'];
          }

          $path_method_spec['operationId'] = $resource_plugin->getPluginId();
          $path_method_spec['schemes'] = ['http'];
          $path_method_spec['parameters'] = array_merge($path_method_spec['parameters'], $this->getRouteParameters($route));
          $path_method_spec['security'] = $this->getSecurity($resource_config, $method);
          $api_paths[$path][$swagger_method] = $path_method_spec;
        }
      }
      return $api_paths;//TEMPORAL
    }
    return $api_paths;
  }

  /**
   * Get parameters for an entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   * @param $method
   *
   * @return array
   */
  protected function getEntityParameters(EntityTypeInterface $entity_type, $method) {
    $parameters = [];
    if (in_array($method, ['GET', 'DELETE', 'PATCH'])) {
      $keys = $entity_type->getKeys();
      $parameters[] = [
        'name' => $entity_type->id(),
        'in' => 'path',
        'default' => '',
        'description' => t('The @id(id) of the @type.', [
          '@id' => $keys['id'],
          '@type' => $entity_type->id(),
        ]),
      ];
    }
    if (in_array($method, ['POST', 'PATCH'])) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $base_fields = $this->field_manager->getBaseFieldDefinitions($entity_type->id());
        foreach ($base_fields as $field_name => $base_field) {
          $parameters[] = $this->getSwaggerFieldParameter($base_field);
        }
      }
    }
    return $parameters;
  }

  /**
   * Gets the a Swagger parameter for a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *
   * @return array
   */
  protected function getSwaggerFieldParameter(FieldDefinitionInterface $field) {
    $parameter = [
      'name' => $field->getName(),
      'required' => $field->isRequired(),
    ];
    $type = $field->getType();
    $date_types = ['changed', 'created'];
    if (in_array($type, $date_types)) {
      $parameter['type'] = 'string';
      $parameter['format'] = 'date-time';
    }
    else {
      $string_types = ['string_long', 'uuid'];
      if (in_array($type, $string_types)) {
        $parameter['type'] = 'string';
      }
    }
    $parameter['default'] = '';
    return $parameter;

  }

  /**
   * Get Swagger parameters for a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *
   * @return array
   */
  protected function getRouteParameters(Route $route) {
    $parameters = [];
    $vars = $route->compile()->getPathVariables();
    foreach ($vars as $var) {
      $parameters[] = [
        'name' => $var,
        'type' => 'string',
        'in' => 'path',
        'default' => '',
        'required' => TRUE,
      ];
    }
    return $parameters;
  }

  /**
   * Gets the matching for route for the resource and method.
   *
   * @param $resource_config
   * @param $method
   *
   * @return \Symfony\Component\Routing\Route
   */
  protected function getRouteForResourceMethod(RestResourceConfigInterface $resource_config, $method) {
    $resource_plugin = $resource_config->getResourcePlugin();
    foreach ($resource_plugin->routes() as $route) {
      $methods = $route->getMethods();
      if (array_search($method, $methods) !== FALSE) {
        return $route;
      }
    };
  }

  /**
   * Get the security information for the a resource.
   *
   * @see http://swagger.io/specification/#securityDefinitionsObject
   *
   * @param \Drupal\rest\RestResourceConfigInterface $resource_config
   * @param $method
   *
   * @return array
   */
  protected function getSecurity(RestResourceConfigInterface $resource_config, $method) {
    $security = [];
    foreach ($resource_config->getAuthenticationProviders($method) as $auth) {
      switch ($auth) {
        case 'basic_auth':
          $security['basic_auth'] = [
            'type' => 'basic',
          ];
      }
    }
    // @todo Handle tokens that need to be set in headers.
    return $security;
  }

}
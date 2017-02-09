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
use Swagger\Annotations;

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
        $data['tags'] = ['Rest Resources'];
        $data['path'] = $path;
        $data['method'] = $method;
        $data['responses'] = [
          "200"=> [
            "description"=> "OK",
            /*"schema"=> [
              "ref"=> "#/definitions/Event"
            ]*/
          ]
        ];
        $data['responses'] = [];//new Annotations\Response([]);
        $annotation_path = [];
        switch ($method) {
          case "get":
            $annotation_path = new Annotations\Get($data);
          break;
          case "post":
            $annotation_path = new Annotations\Post($data);
          break;
          case "put":
            $annotation_path = new Annotations\Put($data);
          break;
          case "patch":
            $annotation_path = new Annotations\Patch($data);
          break;
          case "delete":
            $annotation_path = new Annotations\Delete($data);
          break;
          case "head":
            $annotation_path = new Annotations\Head($data);
          break;
          case "options":
            $annotation_path = new Annotations\Options($data);
          break;
        }
        if ($annotation_path != []) {
          $resource_paths[] = $annotation_path;
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
            /*$path_method_spec['summary'] = t('@method a @entity_type', [
              '@method' => ucfirst($swagger_method),
              '@entity_type' => $entity_type->getLabel(),
            ]);*/

            $path_method_spec['consumes'] = ['application/json'];
            $path_method_spec['produces'] = ['application/json'];
            $path_method_spec['parameters'] += $this->getEntityParameters($entity_type, $method);

          }
          else {
            $path_method_spec['summary'] = $resource_plugin->getPluginDefinition()['label'];
          }

          $path_method_spec['operationId'] = $resource_plugin->getPluginId();
          $path_method_spec['schemes'] = ['http'];//variable
          $path_method_spec['parameters'] = array_merge($path_method_spec['parameters'], $this->getRouteParameters($route));
          $api_paths[$path][$swagger_method] = $path_method_spec;
        }
      }
      //return $api_paths;//TEMPORAL
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
      'description' => $field->getDescription()
    ];
    $drupal_schema = $field->getSchema();
    $drupal_type = isset($drupal_schema['columns']['value']['type']) ? $drupal_schema['columns']['value']['type'] : '';
    $drupal_size = isset($drupal_schema['columns']['value']['size']) ? $drupal_schema['columns']['value']['size'] : '';
    $swagger_types = $this->getSwaggerDataTypesbyDrupalTypes($drupal_type, $drupal_size, $field->getName());
    $parameter['type'] = $swagger_types['type'];
    $parameter['format'] = $swagger_types['format'];
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
   * Function getSwaggerDataTypesbyDrupalTypes().
   *
   * @return array
   *  Get swagger data type by drupal data type.
   */
  function getSwaggerDataTypesbyDrupalTypes($drupal_type, $drupal_size = '', $drupal_name) {
    $swagger_type = '';
    $swagger_format = '';
    $ds_data_types = $this->getDrupalSwaggerDataTypes();
    if (isset($ds_data_types[$drupal_type])) {
      $swagger_type = $ds_data_types[$drupal_type]['type'];
      if ($drupal_size == '') {
        $swagger_format = $ds_data_types[$drupal_type]['default_format'];
      } 
      else {
        foreach($ds_data_types[$drupal_type]['formats'] as $key => $value) {
          if ($drupal_size == $key) {
            $swagger_format = $value;
          }
        }
      }
    }
    if ($drupal_type == 'float' && $drupal_size == 'big') {
      $swagger_type = 'number';
      $swagger_format = 'double';
    }
    if ($drupal_type == 'int' && $drupal_size == 'tiny') {
      $swagger_type = 'boolean';
      $swagger_format = '';
    }
    if ($drupal_type == 'text' && $drupal_size == 'tiny') {
      $swagger_type = 'string';
      $swagger_format = 'binary';
    }
    $date_types = ['changed', 'created'];
    if (in_array($drupal_name, $date_types)) {
      $swagger_type = 'string';
      $swagger_format = 'date-time';
    }
    if ($swagger_type == '') {
      $swagger_type = 'string';
    }
    return [
      'type' => $swagger_type,
      'format' => $swagger_format
    ];
  }

  /**
   * Function getDrupalSwaggerDataTypes().
   *
   * @return array
   *  Get special array with drupal and swagger datatypes.
   */
  private function getDrupalSwaggerDataTypes() {
    return [
      'serial' => [
        'type' => 'integer',
        'default_format' => 'int32',
        'formats' => [
          'tiny' => 'int32',
          'small' => 'int32',
          'medium' => 'int32',
          'normal' => 'int32',
          'big' => 'int64',
        ],
      ],
      'int' => [
        'type' => 'integer',
        'default_format' => 'int32',
        'formats' => [
          'small' => 'int32',
          'medium' => 'int32',
          'normal' => 'int32',
          'big' => 'int64',
        ],
      ],
      'float' => [
        'type' => 'number',
        'default_format' => 'float',
        'formats' => [
          'tiny' => 'float',
          'small' => 'float',
          'medium' => 'float',
          'normal' => 'float',
        ],
      ],
      'numeric' => [
        'type' => 'integer',
        'default_format' => 'int64',
        'formats' => [
          'normal' => 'int64',
        ],
      ],
      'varchar' => [
        'type' => 'string',
        'default_format' => '',
        'formats' => [
          'normal' => '',
        ],
      ],
      'varchar_ascii' => [
        'type' => 'string',
        'default_format' => '',
        'formats' => [
          'normal' => '',
        ],
      ],
      'char' => [
      'type' => 'string',
        'default_format' => '',
        'formats' => [
          'normal' => '',
        ],
      ],
      'text' => [
        'type' => 'string',
        'default_format' => '',
        'formats' => [
          'tiny' => '',
          'small' => '',
          'medium' => '',
          'normal' => '',
        ],
      ],
    ];
  }

}
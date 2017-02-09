<?php

namespace Drupal\swagger\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;

/**
 * SwaggerScanForm form.
 */
class SwaggerScanForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swagger_scan_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('swagger.settings');
    $form['swagger_scan_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan folder'),
      '#description' => $this->t('A local folder system path where swagger will scan the code. Example: modules/custom'),
      '#attributes' => [
        'placeholder' => $this->t('modules/custom'),
        'autofocus' => TRUE,
      ],
      '#default_value' => $config->get('swagger_scan_folder') ?: 'modules/custom',
      '#required' => TRUE,
    ];
    $form['swagger_scan_output'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan output'),
      '#description' => $this->t('A local file system path where swagger.json will be stored. Example: sites/default/files'),
      '#field_suffix' => '/swagger.json',
      '#attributes' => [
        'placeholder' => $this->t('sites/default/files/swagger'),
        'autofocus' => TRUE,
      ],
      '#default_value' => $config->get('swagger_scan_output') ?: 'sites/default/files/swagger',
      '#required' => TRUE,
    ];
    $form['swagger_ui_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Swagger UI path'),
      '#description' => $this->t('Define a path to Swagger UI Page. Example: /swagger/swagger-ui. After submitting "clear cache"'),
      '#attributes' => [
        'placeholder' => $this->t('/swagger/swagger-ui'),
        'autofocus' => TRUE,
      ],
      '#default_value' => $config->get('swagger_ui_path') ?: '/swagger/swagger-ui',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration and Scan code'),
    ];
    require_once 'core/includes/install.inc';
    if (drupal_verify_install_file('./' . $config->get('swagger_scan_output') . '/swagger.json', FILE_EXIST)) {
      $url = Url::fromUri(file_create_url($config->get('swagger_scan_output') . '/swagger.json'));
      $link_options = [
        'attributes' => [
          'target' => ['_blank'],
        ],
      ];
      $url->setOptions($link_options);
      $swagger_json_link = \Drupal::l(t('/swagger.json'), $url);
      $form['swagger_scan_output']['#field_suffix'] = $swagger_json_link;
      // Swagger ui link.
      if (drupal_verify_install_file('libraries/swagger-ui/dist/swagger-ui.js', FILE_EXIST)) {
        $path_swagger_ui = Url::fromRoute('swagger.swaggerui');
        $swagger_ui_link = \Drupal::l(t('Swagger UI'), $path_swagger_ui);
        $form['swagger_ui_path']['#field_suffix'] = $swagger_ui_link;
      }
      else {
        drupal_set_message($this->t('clone https://github.com/swagger-api/swagger-ui.git and move the "swagger-ui" folder to /libraries/'), 'warning', FALSE);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();
    $config = $this->config('swagger.settings');
    foreach ($values as $key => $value) {
      $config->set($key, $form_state->getValue($key))->save();
    }
    $config->save();
    // Run Scan code.
    global $base_url;
    $swagger = \Drupal::service('config.swagger');
    $swagger->generateSwaggerFile($base_url);
    $form_state->setRedirect('swagger_scan.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['swagger.settings'];
  }

}

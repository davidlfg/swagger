<?php

namespace Drupal\swagger\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * SwaggerBasicDocumentationForm form.
 */
class SwaggerBasicDocumentationForm extends ConfigFormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'swagger_basic_documentation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('swagger.settings');
    $config_system = $this->config('system.site');
    $form = parent::buildForm($form, $form_state);
    $form['info'] = array(
      '#type' => 'fieldset',
      '#description' => $this->t('The object provides metadata about the API. The metadata can be used by the clients if needed, and can be presented in the Swagger-UI for convenience.'),
    );
    $form['info']['swagger_swagger_version'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Swagger Specification version'),
      '#description' => $this->t('Specifies the Swagger Specification version being used. It can be used by the Swagger UI and other clients to interpret the API listing.'),
      '#default_value' => $config->get('swagger_swagger_version') ?: '2.0',
      '#required' => TRUE,
      '#disabled' => TRUE,
    );
    $form['info']['swagger_swagger_base_path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Base path'),
      '#description' => $this->t('The base path on which the API is served, which is relative to the host. If it is not included, the API is served directly under the host. The value MUST start with a leading slash (/).'),
      '#default_value' => $config->get('swagger_swagger_base_path') ?: '/',
    );
    $form['info']['swagger_swagger_schemes'] = array(
      '#type' => 'select',
      '#title' => $this->t('Schemes'),
      '#multiple' => TRUE,
      '#description' => $this->t('The transfer protocol of the API.'),
      '#options' => $this->getTranferProtocol(),
      '#default_value' => $config->get('swagger_swagger_schemes'),
    );
    $form['info']['swagger_info_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title of the application.'),
      '#default_value' => $config->get('swagger_info_title') ?: $config_system->get('name'),
      '#required' => TRUE,
    );
    $form['info']['swagger_info_version'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Version'),
      '#description' => $this->t('Provides the version of the application API (not to be confused by the specification version).'),
      '#default_value' => $config->get('swagger_info_version'),
      '#required' => TRUE,
      '#attributes' => array(
        'placeholder' => $this->t('1.0'),
        'autofocus' => TRUE,
      ),
    );
    $form['info']['swagger_info_description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of the application.'),
      '#default_value' => $config->get('swagger_info_description'),
    );
    $form['info']['swagger_info_terms_service'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Terms of service'),
      '#description' => $this->t('The Terms of Service for the API. Enter path or URL'),
      '#default_value' => $config->get('swagger_info_terms_service'),
    );
    // Contact.
    $form['info']['contact_needs'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show information about contact?'),
      '#default_value' => $config->get('contact_needs'),
    );
    $form['info']['contact'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Contact'),
      '#description' => $this->t('The contact information for the exposed API.'),
      '#states' => array(
        'invisible' => array(
          'input[name="contact_needs"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['info']['contact']['swagger_info_contact_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The identifying name of the contact person/organization.'),
      '#default_value' => $config->get('swagger_info_contact_name'),
      '#attributes' => array(
        'placeholder' => $this->t('API Support'),
        'autofocus' => TRUE,
      ),
    );
    $form['info']['contact']['swagger_info_contact_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The URL pointing to the contact information. MUST be in the format of a URL.'),
      '#default_value' => $config->get('swagger_info_contact_url'),
    );
    $form['info']['contact']['swagger_info_contact_email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#description' => $this->t('The email address of the contact person/organization. MUST be in the format of an email address.'),
      '#default_value' => $config->get('swagger_info_contact_email'),
    );
    // License.
    $form['info']['license_needs'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show information about the license?'),
      '#default_value' => $config->get('license_needs'),
    );
    $form['info']['license'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('License'),
      '#description' => $this->t('The license information for the exposed API.'),
      '#states' => array(
        'invisible' => array(
          'input[name="license_needs"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['info']['license']['swagger_info_license_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('The license name used for the API.'),
      '#default_value' => $config->get('swagger_info_license_name'),
      '#states' => array(
        'required' => array(
          ':input[name="license_needs"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['info']['license']['swagger_info_license_url'] = array(
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#description' => $this->t('A URL to the license used for the API. MUST be in the format of a URL.'),
      '#default_value' => $config->get('swagger_info_license_url'),
    );
    $form['info']['swagger_swagger_consumes'] = array(
      '#type' => 'select',
      '#title' => $this->t('Consumes'),
      '#multiple' => TRUE,
      '#description' => $this->t('This is global to all APIs but can be overridden on specific API calls.'),
      '#options' => $this->getMineTypeDefinitions(),
      '#default_value' => $config->get('swagger_swagger_consumes'),
    );
    $form['info']['swagger_swagger_produces'] = array(
      '#type' => 'select',
      '#title' => $this->t('Produces'),
      '#multiple' => TRUE,
      '#description' => $this->t('This is global to all APIs but can be overridden on specific API calls.'),
      '#options' => $this->getMineTypeDefinitions(),
      '#default_value' => $config->get('swagger_swagger_produces'),
    );
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
      $keys_field = [
        'swagger_swagger_schemes',
        'swagger_swagger_produces',
        'swagger_swagger_consumes',
      ];
      if (in_array($key, $keys_field)) {
        $array_value = array_values($form_state->getValue($key));
        $config->set($key, $array_value)->save();
      }
      else {
        $config->set($key, $form_state->getValue($key))->save();
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['swagger.settings'];
  }

  /**
   * Function getTranferProtocol().
   *
   * @return array
   *   List of tranfer Protocol.
   */
  public function getTranferProtocol($key = NULL) {
    return [
      'http' => 'http',
      'https' => 'https',
      'ws' => 'ws',
      'wss' => 'wss',
    ];
  }

  /**
   * Function getMineTypeDefinitions().
   *
   * @return array
   *   List of mine type definitions.
   */
  public function getMineTypeDefinitions() {
    return [
      'text/plain; charset=utf-8' => 'text/plain; charset=utf-8',
      'application/json' => 'application/json',
      'application/xml' => 'application/xml',
      'application/vnd.github+json' => 'application/vnd.github+json',
      'application/vnd.github.v3+json' => 'application/vnd.github.v3+json',
      'application/vnd.github.v3.raw+json' => 'application/vnd.github.v3.raw+json',
      'application/vnd.github.v3.text+json' => 'application/vnd.github.v3.text+json',
      'application/vnd.github.v3.html+json' => 'application/vnd.github.v3.text+json',
      'application/vnd.github.v3.full+json' => 'application/vnd.github.v3.full+json',
      'application/vnd.github.v3.diff' => 'application/vnd.github.v3.diff',
      'application/vnd.github.v3.patch' => 'application/vnd.github.v3.patch',
    ];
  }

}

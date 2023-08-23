<?php

namespace Drupal\os2web_nemlogin\Plugin\os2web\NemloginAuthProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\os2web_nemlogin\Plugin\AuthProviderBase;
use SimpleSAML\Auth\Simple;

define('OS2WEB_NEMLOGIN_SIMPLESAML_AUTH_METHOD', 'default-sp');

/**
 * Defines a plugin for Nemlogin auth via SimpleSAML.
 *
 * @AuthProvider(
 *   id = "simplesaml",
 *   label = @Translation("SimpleSAML Nemlogin auth provider"),
 * )
 */
class SimpleSaml extends AuthProviderBase {

  /**
   * Authorization values array.
   *
   * @var SimpleSAML_Auth_Simple
   */
  private $as;

  /**
   * SimpleSaml constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    try {
      if ($dir = Settings::get('simplesamlphp_dir')) {
        include_once $dir . '/lib/_autoload.php';
      }

      $this->as = new Simple($this->configuration['nemlogin_simplesaml_default_auth']);
    }
    catch (\Exception $e) {
      \Drupal::logger('OS2Web Nemlogin SimpleSAML')
        ->error(t('Cannot initialize simplesaml request: @message', ['@message' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isInitialized() {
    if ($initialized = $this->as instanceof Simple) {
      try {
        $this->as->getAuthSource();
      }
      catch (\Exception $e) {
        \Drupal::logger('OS2Web Nemlogin SimpleSAML')
          ->error(t('Cannot initialize simplesaml request: @message', ['@message' => $e->getMessage()]));
        $initialized = FALSE;
      }
    }

    return $initialized;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    if (!$this->isInitialized()) {
      return NULL;
    }

    return $this->as->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticatedPerson() {
    if (!empty($this->fetchValue('cpr'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticatedCompany() {
    if (!empty($this->fetchValue('cvr'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function login() {
    $return_to_url = $this->getReturnUrl();
    if ($this->isInitialized()) {
      $this->as->requireAuth(
        [
          'ReturnTo' => $return_to_url,
        ]
      );
    }
    else {
      $redirect = new TrustedRedirectResponse($return_to_url);
      $redirect->send();
      return $redirect;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout() {
    $return_to_url = $this->getReturnURL();
    if ($this->isInitialized()) {
      $url = $this->as->getLogoutURL($return_to_url);
      $redirect = new TrustedRedirectResponse($url);
      $redirect->send();
      return $redirect;
    }
    else {
      $redirect = new TrustedRedirectResponse($return_to_url);
      $redirect->send();
      return $redirect;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchValue($key) {
    if (empty($this->as) || !$this->as->isAuthenticated()) {
      return NULL;
    }

    // Make first char uppercase and suffixing with NumberIdentifier.
    $key = ucfirst(strtolower($key));
    $key .= 'NumberIdentifier';

    $attrs = $this->as->getAttributes();
    $value = NULL;

    if (is_array($attrs) && isset($attrs["dk:gov:saml:attribute:$key"])) {
      if (is_array($attrs["dk:gov:saml:attribute:$key"]) && isset($attrs["dk:gov:saml:attribute:$key"][0])) {
        $value = $attrs["dk:gov:saml:attribute:$key"][0];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'nemlogin_simplesaml_default_auth' => OS2WEB_NEMLOGIN_SIMPLESAML_AUTH_METHOD,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['nemlogin_simplesaml_default_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Simplesaml default auth method'),
      '#description' => $this->t('Default auth method for simplesaml. Example: default-sp'),
      '#default_value' => $this->configuration['nemlogin_simplesaml_default_auth'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $configuration['nemlogin_simplesaml_default_auth'] = $form_state->getValue('nemlogin_simplesaml_default_auth');

    $this->setConfiguration($configuration);
  }

}

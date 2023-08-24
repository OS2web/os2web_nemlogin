<?php

namespace Drupal\os2web_nemlogin\Plugin\os2web\NemloginAuthProvider;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Site\Settings;
use Drupal\os2web_nemlogin\Plugin\AuthProviderBase;
use SimpleSAML\Auth\Simple;

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
   * Default SP.
   */
  const DEFAULT_SP = 'default-sp';

  /**
   * Spec version: DK-SAML-2.0.
   */
  const SPEC_VERSION_DK_SAML_2_0 = 'DK-SAML-2.0';

  /**
   * Spec version: OIO-SAML-3.0.
   */
  const SPEC_VERSION_OIO_SAML_3_0 = 'OIO-SAML-3.0';

  /**
   * SimpleSAML object.
   *
   * @var \SimpleSAML\Auth\Simple
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

    $configuration = $this->getConfiguration();

    if ($configuration['nemlogin_simplesaml_spec_version'] == self::SPEC_VERSION_DK_SAML_2_0) {
      // Make first char uppercase and suffixing with NumberIdentifier.
      // Expected key = dk:gov:saml:attribute:CprNumberIdentifier.
      $key = 'dk:gov:saml:attribute:' . ucfirst(strtolower($key)) . 'NumberIdentifier';
    }
    elseif ($configuration['nemlogin_simplesaml_spec_version'] == self::SPEC_VERSION_OIO_SAML_3_0) {
      // Expected key = https://data.gov.dk/model/core/eid/cprNumber.
      $key = 'https://data.gov.dk/model/core/eid/' . strtolower($key) . 'Number';
    }

    $attrs = $this->as->getAttributes();
    $value = NULL;

    if (is_array($attrs) && isset($attrs[$key])) {
      if (is_array($attrs[$key]) && isset($attrs[$key][0])) {
        $value = $attrs[$key][0];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAllValues() {
    return $this->as->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'nemlogin_simplesaml_default_auth' => self::DEFAULT_SP,
      'nemlogin_simplesaml_spec_version' => self::SPEC_VERSION_DK_SAML_2_0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['nemlogin_simplesaml_default_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SimpleSAML default auth method'),
      '#description' => $this->t('Default auth method for SimpleSAML. Example: default-sp'),
      '#default_value' => $this->configuration['nemlogin_simplesaml_default_auth'],
      '#required' => TRUE,
    ];

    $form['nemlogin_simplesaml_spec_version'] = [
      '#type' => 'select',
      '#title' => $this->t('SimpleSAML Spec Version'),
      '#options' => [
        self::SPEC_VERSION_DK_SAML_2_0 => 'Default (DK-SAML-2.0)',
        self::SPEC_VERSION_OIO_SAML_3_0 => 'OS2faktor (OIO-SAML-3.0)',
      ],
      '#description' => $this->t('SimpleSAML specification version'),
      '#default_value' => $this->configuration['nemlogin_simplesaml_spec_version'],
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
    $configuration['nemlogin_simplesaml_spec_version'] = $form_state->getValue('nemlogin_simplesaml_spec_version');

    $this->setConfiguration($configuration);
  }

}

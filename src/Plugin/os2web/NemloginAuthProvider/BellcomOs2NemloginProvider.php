<?php

namespace Drupal\os2web_nemlogin\Plugin\os2web\NemloginAuthProvider;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\os2web_nemlogin\Plugin\AuthProviderBase;

define('OS2WEB_NEMLOGIN_BEL_OS2_REST_PATH', '/service/LoginService.php');
define('OS2WEB_NEMLOGIN_BEL_OS2_LOGIN_PATH', '/nemlogin.php');
define('OS2WEB_NEMLOGIN_BEL_OS2_LOGOUT_PATH', '/nemlogout.php');
define('OS2WEB_NEMLOGIN_BEL_OS2_FETCH_ONCE', TRUE);

/**
 * Defines a plugin for Nemlogin auth via IDP.
 *
 * @AuthProvider(
 *   id = "bellcom_os2nemlogin",
 *   label = @Translation("Bellcom OS2 Nemlogin provider (REST)"),
 * )
 */
class BellcomOs2NemloginProvider extends AuthProviderBase {

  /**
   * Identity provider URL.
   *
   * @var string
   */
  private $idpUrl;

  /**
   * Fetch only mode flag.
   *
   * @var bool
   */
  private $fetchOnce;

  /**
   * Authorization object.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->idpUrl = $this->configuration['idp_base_url'];
    $this->fetchOnce = $this->configuration['fetch_once'];

    if (!UrlHelper::isValid($this->idpUrl, TRUE)) {
      \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->warning(t('IDP URL not not valid or empty.'));
      return;
    }

    // Authentification values stored in session.
    if (!isset($_SESSION['nemlogin_idp'])) {
      $_SESSION['nemlogin_idp'] = [];
    }
    $this->values = &$_SESSION['nemlogin_idp'];

    // Init authentication object.
    $this->httpClient = \Drupal::httpClient();

    $url_to_test = [
      $this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_REST_PATH,
      $this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_LOGIN_PATH,
    ];

    // Testing if we have access to all URLs.
    foreach ($url_to_test as $url) {
      if ($this->httpClient->get($url)->getStatusCode() !== 200) {
        \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->error(t('One of the URL\'s is not available: @url', ['@url' => $url]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isInitialized() {
    $url_to_test = [
      $this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_REST_PATH,
      $this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_LOGIN_PATH,
    ];

    // Testing if we have access to all URLs.
    foreach ($url_to_test as $url) {
      try {
        if (!$this->httpClient || $this->httpClient->get($url)->getStatusCode() !== 200) {
          return FALSE;
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->error(t('One of the URL\'s is not available: @url', ['@url' => $url]));
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    // If user has any authenticated data consider it as authenticated.
    return !empty($this->values);
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticatedPerson() {
    // We have to fetch value via parent, in order to avoid possible deletion
    // of value if "fetchOnce" flag is TRUE.
    // It's important that CVR key is empty. There could be both keys in case of
    // using key files.
    if (!empty(parent::fetchValue('cpr')) && empty(parent::fetchValue('cvr'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticatedCompany() {
    // We have to fetch value via parent, in order to avoid possible deletion
    // of value if "fetchOnce" flag is TRUE.
    if (!empty(parent::fetchValue('cvr'))) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function login() {
    if (empty($_REQUEST['token'])) {
      $forward_url = \Drupal::request()->getSchemeAndHttpHost() . \Drupal::request()->getRequestUri();

      $url = Url::fromUri($this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_LOGIN_PATH, [
        'query' => [
          'mnemo' => $this->configuration['mnemo'],
          'forward' => $forward_url,
        ],
      ]);
      $redirect = new TrustedRedirectResponse($url->toString());
      $redirect->send();
      die();
    }

    $token = $_REQUEST['token'];
    $mnemo = $_REQUEST['mnemo'];

    $response = NULL;
    $cpr = NULL;
    $pid = NULL;
    $cvr = NULL;
    $rid = NULL;

    try {
      $attrib = [
      'token' => $token,
      'mnemo' => $mnemo,
      ];

      $request = $this->httpClient->post($this->idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_REST_PATH, ['form_params' => $attrib]);
      $response = json_decode($request->getBody());
    }
    catch (\Exception $e) {
      \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->warning(t('Cannot initialize request: @message', ['@message' => $e->getMessage()]));
    }

    if ($response) {
      // We need to store values between redirects
      // to be able use it in a a signup form.
      // This value will be deleted after first usage.
      // @see $this->fetchValue() method.
      if (isset($response->cpr)) {
        $cprraw = $response->cpr;
        if ($cprraw) {
          $cpr = utf8_decode($cprraw);
          $this->values['cpr'] = $cpr;
        }
      }

      if (isset($response->pid)) {
        $pidraw = $response->pid;
        if ($pidraw) {
          $pid = utf8_decode($pidraw);
          $this->values['pid'] = $pid;
        }
      }

      if (isset($response->cvr)) {
        $cvrraw = $response->cvr;
        if ($cvrraw) {
          $cvr = utf8_decode($cvrraw);
          $this->values['cvr'] = $cvr;
        }
      }

      if (isset($response->rid)) {
        $ridraw = $response->rid;
        if ($ridraw) {
          $rid = utf8_decode($ridraw);
          $this->values['rid'] = $rid;
        }
      }
    }

    if (!$cpr && !$cvr) {
      \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->warning(t('Could not fetch CPR / CVR. Response is empty'));
    }
    if (!$pid && $rid) {
      \Drupal::logger('OS2Web Nemlogin Bellcom OS2')->warning(t('Could not fetch PID / RID. Response is empty'));
    }

    $return_to_url = $this->getReturnUrl();
    return $this->destroySession($return_to_url);
  }

  /**
   * {@inheritdoc}
   */
  public function logout() {
    // Reset all values.
    $this->values = NULL;

    $logoutResponse = $this->destroySession($this->getReturnUrl());
    $logoutResponse->send();
    return $logoutResponse;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchValue($key) {
    $value = parent::fetchValue($key);

    if ($this->fetchOnce) {
      unset($this->values[$key]);
    }
    return $value;
  }

  /**
   * Destroys identity provider session.
   *
   * @param string $callback
   *   Callback URL.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Redirect response.
   */
  private function destroySession($callback) {
    $getParams = http_build_query(
      [
        'RelayState' => $callback,
      ]
    );
    $idp = $this->configuration['idp_base_url'];
    $url = $idp . OS2WEB_NEMLOGIN_BEL_OS2_LOGOUT_PATH . '?' . $getParams;
    $redirect = new TrustedRedirectResponse($url);
    return $redirect;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'idp_base_url' => '',
      'mnemo' => '',
      'fetch_once' => OS2WEB_NEMLOGIN_BEL_OS2_FETCH_ONCE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['idp_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL of IDP system.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['idp_base_url'],
      '#description' => $this->t('E.g. https://nemlogin3.bellcom.dk/simplesaml. NB! Do not include the trailing slash.'),
    ];
    $form['mnemo'] = [
      '#type' => 'textfield',
      '#title' => t('IDP mnemo key.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['mnemo'],
      '#description' => $this->t('Value for IDP mnemo key. Example: bellcom.dk'),
    ];
    $form['fetch_once'] = [
      '#type' => 'checkbox',
      '#title' => t('Use fetch only mode.'),
      '#default_value' => $this->configuration['fetch_once'],
      '#description' => $this->t('User will be logged out immediately after login. User data will be removed from session after first retrieving'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $idpUrl = $form_state->getValue('idp_base_url');
    if (strcmp(substr($idpUrl, -1), '/') === 0) {
      $form_state->setErrorByName('idp_base_url', $this->t('Please remove the trailing slash'));
    }

    if (!UrlHelper::isValid($idpUrl, TRUE)) {
      $form_state->setErrorByName('idp_base_url', $this->t('URL is not valid'));
    }
    else {
      $url_to_test = [
        $idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_REST_PATH,
        $idpUrl . OS2WEB_NEMLOGIN_BEL_OS2_LOGIN_PATH,
      ];

      $client = \Drupal::httpClient();

      // Testing if we have access to all URLs.
      foreach ($url_to_test as $url) {
        try {
          $client->get($url);
        }
        catch (\Exception $e) {
          $form_state->setErrorByName('idp_base_url', $this->t('%url cannot be accessed. Response code: %code', [
            '%url' => $url,
            '%code' => $e->getCode(),
          ]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $configuration['idp_base_url'] = $form_state->getValue('idp_base_url');
    $configuration['mnemo'] = $form_state->getValue('mnemo');
    $configuration['fetch_once'] = $form_state->getValue('fetch_once');

    $this->setConfiguration($configuration);
  }

}

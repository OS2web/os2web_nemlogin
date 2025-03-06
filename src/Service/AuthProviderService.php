<?php

namespace Drupal\os2web_nemlogin\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\os2web_nemlogin\Form\SettingsForm;
use Drupal\os2web_nemlogin\Plugin\AuthProviderInterface;

/**
 * Class AuthProviderService.
 *
 * @package Drupal\os2web_nemlogin\Service
 */
class AuthProviderService {

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Active plugin.
   *
   * @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface
   */
  protected AuthProviderInterface $activePlugin;

  /**
   * AuthProviderService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get(SettingsForm::$configName);
    $this->activePlugin = $this->getPluginInstance($this->getActivePluginId());
  }

  /**
   * Returns active Nemlogin auth provider plugin ID.
   *
   * @return string
   *   Plugin ID.
   */
  public function getActivePluginId() {
    return $this->config->get('active_plugin_id');
  }

  /**
   * Returns active Nemlogin auth provider plugin.
   *
   * @return \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface
   *   Plugin object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getActivePlugin() {
    return $this->activePlugin;
  }

  /**
   * Returns Plugin instance.
   *
   * @param string $plugin_id
   *   String id of the plugin.
   *
   * @return \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface
   *   Plugin object.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPluginInstance($plugin_id) {
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderManager $authProviderManager */
    $authProviderManager = \Drupal::service('plugin.manager.os2web_nemlogin.auth_provider');

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $pluginInstance */
    $pluginInstance = $authProviderManager->createInstance($plugin_id);

    return $pluginInstance;
  }

  /**
   * Returns a list of initialized plugin.
   *
   * @return array
   *   Mapped as ["plugin_id" => "Plugin name"]
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getInitializedPlugins() {
    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderManager $authProviderManager */
    $authProviderManager = \Drupal::service('plugin.manager.os2web_nemlogin.auth_provider');
    $definitions = $authProviderManager->getDefinitions();
    $initializedPlugins = [];
    foreach ($definitions as $definition) {
      /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
      $plugin = $authProviderManager->createInstance($definition['id']);
      if ($plugin->isInitialized()) {
        $initializedPlugins[$definition['id']] = $definition['label'];
      }
    }

    return $initializedPlugins;
  }

  /**
   * Generates a NemID login URL.
   *
   * @param array $options
   *   Array of options, @see \Drupal\Core\Url::fromUri().
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return \Drupal\Core\Url
   *   The generate URL.
   */
  public function getLoginUrl(array $options = [], $plugin_id = NULL) {
    $options += ['absolute' => TRUE];

    if (empty($options['query']['destination'])) {
      $requestUri = \Drupal::request()->getRequestUri();
      $options['query']['destination'] = ltrim($requestUri, '/');
    }

    $url = Url::fromRoute('os2web_nemlogin.login', ['plugin_id' => $plugin_id], $options);

    return $url;
  }

  /**
   * Generates a NemID logout URL.
   *
   * @param array $options
   *   Array of options, @see \Drupal\Core\Url::fromUri().
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return \Drupal\Core\Url
   *   The generate URL.
   */
  public function getLogoutUrl(array $options = [], $plugin_id = NULL) {
    $options += ['absolute' => TRUE];

    if (empty($options['query']['destination'])) {
      $requestUri = \Drupal::request()->getRequestUri();
      $options['query']['destination'] = ltrim($requestUri, '/');
    }

    $url = Url::fromRoute('os2web_nemlogin.logout', ['plugin_id' => $plugin_id], $options);

    return $url;
  }

  /**
   * Generates a NemID link.
   *
   * @param string $login_text
   *   Login link text.
   * @param string $logout_text
   *   Logout link text.
   * @param array $options
   *   Array of options, @see \Drupal\Core\Url::fromUri().
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return string
   *   Generated URL.
   */
  public function generateLink($login_text = NULL, $logout_text = NULL, array $options = [], $plugin_id = NULL) {
    $login_text = isset($login_text) ? $login_text : t('Login with Nemlogin');
    $logout_text = isset($logout_text) ? $logout_text : t('Logout with Nemlogin');

    $plugin = NULL;
    try {
      if ($plugin_id) {
        $plugin = $this->getPluginInstance($plugin_id);
      }
      else {
        $plugin = $this->getActivePlugin();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('OS2Web Nemlogin')->warning(t('Nemlogin authorization object is empty'));
      return NULL;
    }

    if (empty($plugin) || !$plugin->isInitialized()) {
      \Drupal::logger('OS2Web Nemlogin')->warning(t("Nemlogin authorization object doesn't work properly"));
      return NULL;
    }

    return $plugin->isAuthenticated()
      ? Link::fromTextAndUrl($logout_text, $this->getLogoutUrl($options, $plugin_id))
      : Link::fromTextAndUrl($login_text, $this->getLoginUrl($options, $plugin_id));
  }

}

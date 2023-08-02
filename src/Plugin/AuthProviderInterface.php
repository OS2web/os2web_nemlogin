<?php

namespace Drupal\os2web_nemlogin\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an AuthProvider plugin manager.
 *
 * @see \Drupal\os2web_nemlogin\Annotation\AuthProvider
 * @see plugin_api
 */
interface AuthProviderInterface extends PluginFormInterface, PluginInspectionInterface, ConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * Check plugin initialization status.
   *
   * @return bool
   *   Boolean value about plugin initialization status.
   */
  public function isInitialized();

  /**
   * Check authorization status.
   *
   * @return bool
   *   Boolean value about authorization status.
   */
  public function isAuthenticated();

  /**
   * Checks if the authenticated entity is person.
   *
   * @return bool
   *   Boolean value about authorization status.
   */
  public function isAuthenticatedPerson();

  /**
   * Checks if the authenticated entity is person.
   *
   * @return bool
   *   Boolean value about authorization status.
   */
  public function isAuthenticatedCompany();

  /**
   * Main login method.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Redirect response.
   */
  public function login();

  /**
   * Main logout method.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Redirect response.
   */
  public function logout();

  /**
   * Fetch authorization value by key.
   *
   * @param string|array $key
   *   Key for fetching value.
   *   If array is used, performing NestedArray::getValue() lookup.
   *
   * @return string
   *   Authorization value.
   */
  public function fetchValue($key);

  /**
   * Fetches all authorization values with key.
   *
   * @return array
   *   Authorization values array.
   */
  public function fetchAllValues();

  /**
   * Removes all values so that they cannot be used in the future requests.
   */
  public function clearValues();

}

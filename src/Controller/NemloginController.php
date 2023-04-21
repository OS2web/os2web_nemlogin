<?php

namespace Drupal\os2web_nemlogin\Controller;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class NemloginController.
 *
 * @package Drupal\os2web_nemlogin\Controller
 */
class NemloginController extends ControllerBase {

  /**
   * Nemlogin Auth Provider Login callback.
   *
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|null
   *   Redirect response.
   */
  public function login($plugin_id = NULL) {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    $plugin = NULL;
    try {
      if ($plugin_id) {
        $plugin = $authProviderService->getPluginInstance($plugin_id);
      }
      else {
        $plugin = $authProviderService->getActivePlugin();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('OS2Web Nemlogin')->warning(t('Nemlogin authorization object is empty'));
      return NULL;
    }

    return $plugin->login();
  }

  /**
   * Nemlogin Auth Provider Logout callback.
   *
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|null
   *   Redirect response.
   */
  public function logout($plugin_id = NULL) {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    $plugin = NULL;
    try {
      if ($plugin_id) {
        $plugin = $authProviderService->getPluginInstance($plugin_id);
      }
      else {
        $plugin = $authProviderService->getActivePlugin();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('OS2Web Nemlogin')->warning(t('Nemlogin authorization object is empty'));
      return NULL;
    }

    return $plugin->logout();
  }

  /**
   * Test page callback.
   *
   * @param string $plugin_id
   *   Plugin id, if omitted using default.
   *
   * @return array
   *   Build array.
   */
  public function testPage($plugin_id = NULL) {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    $plugin = NULL;
    try {
      if ($plugin_id) {
        $plugin = $authProviderService->getPluginInstance($plugin_id);
      }
      else {
        $plugin = $authProviderService->getActivePlugin();
      }
    }
    catch (\Exception $e) {
      $build[] = [
        '#markup' => 'Nemlogin authorization object is empty',
      ];
      return $build;
    }

    if ($plugin->isAuthenticated()) {
      $cpr = $plugin->fetchValue('cpr');
      $cvr = $plugin->fetchValue('cvr');

      if ($cpr) {
        $build[] = [
          '#markup' => '<p>' . 'You are logged in with CPR: ' . $cpr . '</p>',
        ];
      }
      if ($cvr) {
        $build[] = [
          '#markup' => '<p>' . 'You are logged in with CVR: ' . $cvr . '</p>',
        ];
      }

      if (!$cpr && !$cvr) {
        $build[] = [
          '#markup' => '<p>' . 'You are logged, but neither CPR nor CVR available</p>',
        ];
      }
      $values = $plugin->fetchAllValues();
      $build['values'] = [
        '#type' => 'details',
        '#title' => $this
          ->t('Current session values'),
      ];
      $build['values'][] = [
        '#markup' => '<pre>' . Variable::export($values) . '</pre>',
      ];
    }

    if ($plugin->isInitialized()) {
      $build[] = [
        '#markup' => '<p>' . $authProviderService->generateLink(NULL, NULL, [], $plugin->getPluginId())->toString() . '</p>',
      ];
    }
    else {
      $build[] = [
        '#markup' => '<p>' . 'No plugin select, or selected plugin cannot be initialized' . '</p>',
      ];
    }

    return $build;
  }

}

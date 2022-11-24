<?php

namespace Drupal\os2web_nemlogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class NemloginController.
 *
 * @package Drupal\os2web_nemlogin\Controller
 */
class NemloginController extends ControllerBase {

  /**
   * Nemlogin Auth Provider Login callback.
   */
  public function login() {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $plugin = $authProviderService->getActivePlugin();
    return $plugin->login();
  }

  /**
   * Nemlogin Auth Provider Logout callback.
   */
  public function logout() {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $plugin = $authProviderService->getActivePlugin();
    return $plugin->logout();
  }

  /**
   * Nemlogin session destroy
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function destroySessionByToken() {
    $token = $_POST['token'];

    if ($token) {
      // Find session id.
      /** @var \Drupal\Core\TempStore\SharedTempStore $store */
      $store = \Drupal::service('tempstore.shared')->get('os2web_nemlogin.session_tokens');
      $sid = $store->get($token);

      if ($sid) {
        /** @var \Drupal\Core\Session\SessionHandler $sessionHandler */
        $sessionHandler = \Drupal::service('session_handler');
        $sessionHandler->destroy($sid);

        $store->delete($token);
      }
    }

    return new Response();
  }

  /**
   * Test page callback.
   */
  public function testPage() {
    /** @var \Drupal\os2web_nemlogin\Service\AuthProviderService $authProviderService */
    $authProviderService = \Drupal::service('os2web_nemlogin.auth_provider');

    /** @var \Drupal\os2web_nemlogin\Plugin\AuthProviderInterface $plugin */
    $plugin = $authProviderService->getActivePlugin();
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
    }

    if ($plugin->isInitialized()) {
      $build[] = [
        '#markup' => '<p>' . $authProviderService->generateLink()->toString() . '</p>',
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

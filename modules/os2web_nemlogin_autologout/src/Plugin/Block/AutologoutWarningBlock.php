<?php

namespace Drupal\os2web_nemlogin_autologout\Plugin\Block;

use Drupal\os2web_nemlogin_autologout\AutologoutManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Automated Logout info' block.
 *
 * @Block(
 *   id = "os2web_nemlogin_autologout_warning_block",
 *   admin_label = @Translation("Automated logout info"),
 *   category = @Translation("User"),
 * )
 */
class AutologoutWarningBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The module manager service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The config object for 'os2web_nemlogin_autologout.settings'.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $autoLogoutSettings;

  /**
   * The AutologoutManager service.
   *
   * @var \Drupal\os2web_nemlogin_autologout\AutologoutManager
   */
  protected $manager;

  /**
   * The FormBuilder service.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $builder;

  /**
   * Constructs an AutologoutWarningBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module manager service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Config\Config $autologout_settings
   *   The config object for 'os2web_nemlogin_autologout.settings'.
   * @param \Drupal\os2web_nemlogin_autologout\AutologoutManager $manager
   *   The AutologoutManager service.
   * @param \Drupal\Core\Form\FormBuilder $builder
   *   The FormBuilder service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ModuleHandlerInterface $module_handler,
    DateFormatterInterface $date_formatter,
    Config $autologout_settings,
    AutologoutManager $manager,
    FormBuilder $builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->autoLogoutSettings = $autologout_settings;
    $this->manager = $manager;
    $this->builder = $builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('config.factory')->get('os2web_nemlogin_autologout.settings'),
      $container->get('os2web_nemlogin_autologout.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // @todo This is not the place where we should be doing this.
    $return = [];
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $autologout_manager = $this->manager;
    if ($autologout_manager->preventJs()) {

      // Don't display the block if the user is not going
      // to be logged out on this page.
      return [];
    }

    if ($this->moduleHandler->moduleExists('jstimer') && $this->moduleHandler->moduleExists('jst_timer')) {
      return $this->builder->getForm('Drupal\os2web_nemlogin_autologout\Form\AutologoutBlockForm');
    }
    else {
      $timeout = (int) $autologout_manager->getUserTimeout();
      $markup = $this->t('You will be logged out in @time if this page is not refreshed before then.', ['@time' => $this->dateFormatter->formatInterval($timeout)]);
    }

    return [
      '#type' => 'markup',
      '#markup' => $markup,
    ];
  }

}

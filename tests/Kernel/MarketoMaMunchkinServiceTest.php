<?php

use Drupal\KernelTests\KernelTestBase;
use Drupal\Core\Site\Settings;
use Drupal\marketo_ma\MarketoMaMunchkinInterface;

/**
 * @group marketo_ma3
 */
class MarketoMaServiceTest extends KernelTestBase {

  /**
   * @var \Drupal\marketo_ma\MarketoMaMunchkinInterface The marketo_ma munchkin service.
   */
  protected $munchkin;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'encryption',
    'marketo_ma',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Install config for this module.
    $this->installConfig('marketo_ma');

    // Get the settings object.
    $settings = Settings::getAll();
    // Add a randomly generated encryption key.
    new Settings($settings + ['encryption_key' => base64_encode(random_bytes(32))]);

    // Get the encryption service.
    $encryption_service = \Drupal::service('encryption');

    // Get the API settings.
    $config = \Drupal::configFactory()->getEditable('marketo_ma.settings');

    // Set up required settings.
    $config->set('tracking_method', 'rest');
    $config->set('munchkin.account_id', $encryption_service->encrypt(getenv('marketo_ma_munchkin_account_id')));
    $config->set('rest.client_id', $encryption_service->encrypt(getenv('marketo_ma_rest_client_id')));
    $config->set('rest.client_secret', $encryption_service->encrypt(getenv('marketo_ma_rest_client_secret')));
    $config->save();

    // Get the API client service.
    $this->munchkin = \Drupal::service('marketo_ma.munchkin');
  }

  /**
   * Tests the marketo_ma service.
   */
  public function testMarketoMaService() {
    self::assertTrue($this->munchkin instanceof MarketoMaMunchkinInterface);

    self::assertTrue($this->munchkin->isConfigured());
  }

}

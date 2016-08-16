<?php

namespace Drupal\marketo_ma;

use CSD\Marketo\Client;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encryption\EncryptionTrait;

/**
 * This is a wrapper for the default API client library. It could be switched
 * out by another module that supplies an alternate API client library.
 */
class MarketoMaApiClient implements MarketoMaApiClientInterface {


  // Adds ability to encrypt/decrypt configuration.
  use EncryptionTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The API client library. @see: https://github.com/dchesterton/marketo-rest-api.
   *
   * @var \CSD\Marketo\ClientInterface
   */
  private $client;

  /**
   * The config used to instantiate the REST client.
   *
   * @var array
   */
  private $client_config;

  /**
   * Creates the Marketo API client wrapper service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;

    $config = $this->config();

    // Build the config for the REST API Client.
    $this->client_config = [
      'client_id' => $this->decrypt($config->get('rest.client_id')),
      'client_secret' => $this->decrypt($config->get('rest.client_secret')),
      'munchkin_id' => $this->decrypt($config->get('munchkin.account_id')),
    ];
  }

  /**
   * Get's marketo_ma settings.
   * @return \Drupal\Core\Config\ImmutableConfig
   */
  protected function config() {
    return $this->configFactory->get('marketo_ma.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getFields() {
    $fields_result = $this->getClient()->getFields()->getResult();

    array_walk($fields_result, function (&$field_item) {
      $field_item['default_name'] = $field_item['rest']['name'];
    });

    return $fields_result;
  }

  /**
   * {@inheritdoc}
   */
  public function canConnect() {
    return !empty($this->client_config['munchkin_id'])
      && !empty($this->client_config['client_id'])
      && !empty($this->client_config['munchkin_id']);
  }

  /**
   * Instantiate the REST API client.
   */
  protected function getClient() {
    if (!isset($this->client)) {
      $this->client = Client::factory($this->client_config);
    }
    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function getLead($key, $type) {
    return $this->getClient()->getLeadByFilterType($key, $type)->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function getLeadActivity($key, $type) {
    // Get the lead by the filter type.
    $lead = $this->getLead($key, $type);

    /**
     * @todo: Use configuration form to manage the default activity types.
     *
     * The other option is to get all activity type ids, and request activity in
     * groups of 10 ids and aggregate the results.
     */
    // Use ids for common activities (max 10 activity ids pre request).
    $activity_type_ids = '1,2,3';

    // A paging token is required by the activities.json call.
    $paging_token = $this->getClient()->getPagingToken(date('c'))->getNextPageToken();
    // Calls get lead activities on the API client.
    return $this->getClient()->getLeadActivity($paging_token, $lead['id'], $activity_type_ids)->getLeadActivity();
  }

  /**
   * {@inheritdoc}
   */
  public function syncLead($lead, $key = 'email', $cookie = null, $options = []) {
    // Add the cookie to the lead's info if it has been set.
    if (!empty($cookie)) $lead['marketoCookie'] = $cookie;
    return $this->getClient()->createOrUpdateLeads([$lead], $key, $options)->getResult();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLead($leads, $args = array()) {
    return $this->getClient()->deleteLead($leads)->getResult();
  }

}

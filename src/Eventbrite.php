<?php

namespace Upanupstudios\Eventbrite\Php\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * The Eventbrite class.
 */
class Eventbrite {

  /**
   * The Authorization URL.
   *
   * @var string
   */
  private $authorizationUrl = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';

  /**
   * The Token URL.
   *
   * @var string
   */
  private $tokenUrl = 'https://authz.constantcontact.com/oauth2/default/v1/token';

  /**
   * The REST API URL.
   *
   * @var string
   */
  private $apiUrl = 'https://www.eventbriteapi.com/v3';

  /**
   * The config instance.
   *
   * @var Config
   */
  private $config = NULL;

  /**
   * The client instance.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $httpClient, Config $config = NULL) {
    $this->httpClient = $httpClient;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function getApiUrl() {
    return $this->apiUrl;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): Config {
    return $this->config;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl($clientId, $redirectURI, $scope, $state) {
    // @todo Scope can be array.
    // Create authorization URL.
    $authURL = $this->authorizationUrl . "?client_id=" . $clientId . "&scope=" . $scope . "&response_type=code&state=" . $state . "&redirect_uri=" . $redirectURI;

    return $authURL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken($redirectURI, $clientId, $clientSecret, $code) {
    // Create full request URL.
    $token_uri = $this->tokenUrl . '?code=' . $code . '&redirect_uri=' . $redirectURI . '&grant_type=authorization_code';
    $credentials = base64_encode($clientId . ':' . $clientSecret);

    return $this->request('POST', $token_uri, [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . $credentials,
        'Cache-Control' => 'no-cache',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshToken($refreshToken, $clientId, $clientSecret) {
    // Create full request URL.
    $token_uri = $this->tokenUrl . '?refresh_token=' . $refreshToken . '&grant_type=refresh_token';
    $credentials = base64_encode($clientId . ':' . $clientSecret);

    return $this->request('POST', $token_uri, [
      'headers' => [
        'Accept' => 'application/json',
        'Authorization' => 'Basic ' . $credentials,
        'Cache-Control' => 'no-cache',
        'Content-Type' => 'application/x-www-form-urlencoded',
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function request(string $method, string $url, array $options = []) {
    try {
      $defaultOptions = [];

      if (!empty($this->config)) {
        $defaultOptions = [
          'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $this->config->getAccessToken(),
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
          ],
        ];
      }

      if (!empty($options)) {
        $options = array_merge_recursive($defaultOptions, $options);
      }
      else {
        $options = $defaultOptions;
      }

      $response = $this->httpClient->request($method, $url, $options);
    }
    catch (RequestException $exception) {
      $response = $exception->getResponse();
    }

    // Get body.
    $body = $response->getBody();
    $body = $body->__toString();

    // Return as array.
    $response = json_decode($body, TRUE);

    return $response;
  }

  /**
   * Throws InvalidArgumentException if $class does not exist.
   *
   * @throws \InvalidArgumentException
   */
  public function api(string $class) {
    switch ($class) {
      case 'contacts':
        $api = new Contacts($this);
        break;

      case 'contactLists':
        $api = new ContactLists($this);
        break;

      case 'emailCampaigns':
        $api = new EmailCampaigns($this);
        break;

      case 'emailCampaignActivities':
        $api = new EmailCampaignActivities($this);
        break;

      default:
        throw new \InvalidArgumentException("Undefined api instance called: '$class'.");
    }

    return $api;
  }

  /**
   * {@inheritdoc}
   */
  public function __call(string $name, array $args): object {
    try {
      return $this->api($name);
    }
    catch (\InvalidArgumentException $e) {
      throw new \BadMethodCallException("Undefined method called: '$name'.");
    }
  }

}

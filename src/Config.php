<?php

namespace Upanupstudios\Eventbrite\Php\Client;

/**
 * The Config class.
 */
final class Config {

  /**
   * The Access Token.
   *
   * @var string
   */
  private $accessToken;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $accessToken)
  {
    $this->accessToken = $accessToken;
  }

  /**
   * Get Access Token.
   */
  public function getAccessToken(): string
  {
    return $this->accessToken;
  }

}

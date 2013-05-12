<?php

/**
 * This file is part of the pantarei/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pantarei\OAuth2\Request;

use Pantarei\OAuth2\Database\Database;
use Pantarei\OAuth2\Exception\AccessDeniedException;
use Pantarei\OAuth2\Exception\InvalidClientException;
use Pantarei\OAuth2\Exception\InvalidGrantException;
use Pantarei\OAuth2\Exception\InvalidRequestException;
use Pantarei\OAuth2\Exception\InvalidScopeException;
use Pantarei\OAuth2\Exception\ServerErrorException;
use Pantarei\OAuth2\Exception\TemporarilyUnavailableException;
use Pantarei\OAuth2\Exception\UnauthorizedClientException;
use Pantarei\OAuth2\Exception\UnsupportedGrantTypeException;
use Pantarei\OAuth2\Exception\UnsupportedResponseTypeException;
use Pantarei\OAuth2\GrantType\AuthorizationCodeGrantType;
use Pantarei\OAuth2\GrantType\ClientCredentialsGrantType;
use Pantarei\OAuth2\GrantType\PasswordGrantType;
use Pantarei\OAuth2\GrantType\RefreshTokenGrantType;
use Pantarei\OAuth2\Util\ParamUtils;

/**
 * Access token request implementation.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class AccessTokenRequest implements RequestInterface
{
  /**
   * Internal function used to get the client credentials from HTTP basic
   * auth or POST data.
   *
   * @return
   *   A array containing the client identifier and password, with keys:
   *   - client_id
   *   - client_secret
   */
  private function getClientCredentials()
  {
    // At least one (and only one) of client credentials method required.
    if (!isset($_SERVER["PHP_AUTH_USER"]) && !isset($_POST["client_id"])) {
      throw new InvalidClientException();
    }
    elseif (isset($_SERVER["PHP_AUTH_USER"]) && isset($_POST["client_id"])) {
      throw new InvalidRequestException();
    }

    $query = array();

    // Try basic auth.
    if (isset($_SERVER["PHP_AUTH_USER"])) {
      $query = array(
        'client_id' => $_SERVER["PHP_AUTH_USER"],
        'client_secret' => isset($_SERVER["PHP_AUTH_PW"]) ? $_SERVER["PHP_AUTH_PW"] : '',
      );
    }
    // Try POST
    elseif ($_POST && isset($_POST["client_id"])) {
      $query = array(
        'client_id' => $_POST["client_id"],
        'client_secret' => isset($_POST["client_secret"]) ? $_POST["client_secret"] : '',
      );
    }

    return ParamUtils::filter($query, array(
      'client_id',
      'client_secret',
    ));
  }

  public function validateRequest()
  {
    $filtered_query = ParamUtils::filter($_POST, array(
      'client_id',
      'code',
      'grant_type',
      'password',
      'redirect_uri',
      'refresh_token',
      'scope',
      'username',
    ));

    // grant_type myst be specified.
    if (!isset($filtered_query['grant_type'])) {
      throw new InvalidRequestException();
    }

    // TODO: Make sure we've implemented the requested grant type.
    //if (!in_array($input["grant_type"], $this->getSupportedGrantTypes()))
    //  $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

    // Authorize the client, and stop here if invalid.
    $client = Database::findOneBy('Clients', $this->getClientCredentials());
    if ($client == NULL) {
      throw new UnauthorizedClientException();
    }

    // Let's initialize grant_type object here.
    $grant_type = NULL;
    switch ($filtered_query['grant_type']) {
      case 'authorization_code':
        // Both grant_type, code, redirect_uri and client_id are required.
        if (!isset($filtered_query['code']) || !isset($filtered_query['redirect_uri'])) {
          throw new InvalidRequestException();
        }

        $grant_type = new AuthorizationCodeGrantType();

        $grant_type->setCode($filtered_query['code'])
          ->setRedirectUri($filtered_query['redirect_uri'])
          ->setClientId($client->getClientId());

        break;
      case 'client_credentials':
        $grant_type = new ClientCredentialsGrantType();

        // Validate that the requested scope is supported.
        if (isset($_POST['scope'])) {
          if(!isset($filtered_query['scope'])) {
            throw new InvalidScopeException();
          }
          $grant_type->setScope($filtered_query['scope']);
        }

        break;
      case 'password':
        // Both grant_type, username and password are required.
        if (!isset($filtered_query['username']) || !isset($filtered_query['password'])) {
          throw new InvalidRequestException();
        }

        $grant_type = new PasswordGrantType();
        $grant_type->setUsername($filtered_query['username'])
          ->setPassword($filtered_query['password']);

        // Validate that the requested scope is supported.
        if (isset($_POST['scope'])) {
          if(!isset($filtered_query['scope'])) {
            throw new InvalidScopeException();
          }
          $grant_type->setScope($filtered_query['scope']);
        }

        break;
      case 'refresh_token':
        // Both grant_type and refresh_token are required.
        if (!isset($filtered_query['refresh_token'])) {
          throw new InvalidRequestException();
        }

        $grant_type = new RefreshTokenGrantType();
        $grant_type->setRefreshToken($filtered_query['refresh_token']);

        // Validate that the requested scope is supported.
        if (isset($_POST['scope'])) {
          if(!isset($filtered_query['scope'])) {
            throw new InvalidScopeException();
          }
          $grant_type->setScope($filtered_query['scope']);
        }

        break;
    }

    return $grant_type;
  }
}

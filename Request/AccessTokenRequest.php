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
  public function validateRequest($query = array(), $request = array())
  {
    $filtered_query = ParamUtils::filter($query, array(
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

    // Validate the client credentials.
    $client = $this->validateClientCredentials();

    // TODO: Make sure we've implemented the requested grant type.
    //if (!in_array($input["grant_type"], $this->getSupportedGrantTypes()))
    //  $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

    // Let's initialize grant_type object here.
    $grant_type = NULL;
    switch ($filtered_query['grant_type']) {
      case 'authorization_code':
        $grant_type = new AuthorizationCodeGrantType();
        break;
      case 'client_credentials':
        $grant_type = new ClientCredentialsGrantType();
        break;
      case 'password':
        $grant_type = new PasswordGrantType();
        break;
      case 'refresh_token':
        $grant_type = new RefreshTokenGrantType();
        break;
    }

    return $grant_type;
  }

  protected function validateClientCredentials()
  {

  }
}

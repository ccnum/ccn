<?php

if (!defined('_ECRIRE_INC_VERSION')) {
  return;
}

class OAuth2_PKCE {

  /**
   * Génère un code_verifier conforme RFC 7636
   */
  public static function generate_verifier(): string {
    return rtrim(strtr(
      base64_encode(random_bytes(64)),
      '+/', '-_'
    ), '=');
  }

  /**
   * Génère le code_challenge à partir du verifier
   */
  public static function generate_challenge(
    string $verifier,
    string $method = 'S256'
  ): string {
    if (strtoupper($method) === 'S256') {
      return rtrim(strtr(
        base64_encode(hash('sha256', $verifier, true)),
        '+/', '-_'
      ), '=');
    }

    // méthode "plain"
    return $verifier;
  }

}

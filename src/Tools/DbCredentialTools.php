<?php

namespace clever_systems\mmm_builder\Tools;

/**
 * @file DbCredentialTools.php
 */
class DbCredentialTools {

  /**
   * @param string $credentials
   * @return array[array]
   */
  public static function getDbCredentialsFromDbUrl($credentials) {
    // Taken from drush_convert_db_from_db_url()
    $parts = parse_url($credentials);
    if ($parts) {
      // Fill in defaults to prevent notices.
      $parts += array(
        'scheme' => NULL,
        'user' => NULL,
        'pass' => NULL,
        'host' => NULL,
        'port' => NULL,
        'path' => NULL,
      );
      $parts = (object) array_map('urldecode', $parts);
      $credentials = array(
        'driver' => $parts->scheme == 'mysqli' ? 'mysql' : $parts->scheme,
        'username' => $parts->user,
        'password' => $parts->pass,
        'host' => $parts->host,
        'port' => $parts->port,
        'database' => ltrim($parts->path, '/'),
      );
      return $credentials;
    }
    return $credentials;
  }

  /**
   * @param array[array] $credentials
   * @param string[string] $replacements
   * @return array[array]
   */
  public static function substituteInDbCredentials($credentials, $replacements) {
    $placeholders = array_keys($replacements);
    foreach ($credentials as &$server_credential) {
      foreach ($server_credential as &$value) {
        $value = str_replace($placeholders, $replacements, $value);
      }
    }
    return $credentials;
  }

}
<?php

namespace machbarmacher\localsettings\Tools;

/**
 * @file DbCredentialTools.php
 */
class DbCredentialTools {

  /**
   * @param string $db_url
   * @return array[]
   */
  public static function getDbCredentialsFromDbUrl($db_url) {
    // Taken from drush_convert_db_from_db_url()
    $parts = parse_url($db_url);
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
    return [];
  }

  /**
   * @param array[] $credential
   * @param string[string] $replacements
   * @return array[]
   */
  public static function substituteInDbCredentials($credential, $replacements) {
    $placeholders = array_keys($replacements);
    foreach ($credential as &$value) {
      $value = str_replace($placeholders, $replacements, $value);
    }
    return $credential;
  }

}

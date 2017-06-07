<?php


namespace machbarmacher\localsettings\Commands;


use Drush\Log\LogLevel;

trait FileExistsTrait {
  /**
   * @param string $filename
   * @return bool
   */
  protected function checkSourceDoesExist($filename) {
    if (!file_exists($filename)) {
      drush_log(dt('Source does not exist: !file', ['!file' => $filename]), LogLevel::ERROR);
      return FALSE;
    }
    return TRUE;
  }
  /**
   * @param string $filename
   * @return bool
   */
  protected function checkTargetDoesNotExist($filename) {
    if (file_exists($filename) && !drush_get_option(['f', 'force'])) {
      drush_log(dt('Target already exists: !file', ['!file' => $filename]), LogLevel::ERROR);
      return FALSE;
    }
    return TRUE;
  }
}

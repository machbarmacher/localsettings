<?php


namespace clever_systems\mmm_builder\Commands;


use Drush\Log\LogLevel;

trait FileExistsTrait {
  /**
   * @param string $filename
   * @return bool
   */
  protected function checkFile($filename) {
    if (file_exists($filename) && !drush_get_option(['f', 'force'])) {
      drush_log(dt('File already exists: !file', ['!file' => $filename]), LogLevel::ERROR);
      return FALSE;
    }
    return TRUE;
  }
}

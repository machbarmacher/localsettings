<?php
/**
 * @file GlobalProject.php
 */

namespace clever_systems\mmm2;


class GlobalProject {
  /** @var Project */
  protected static $project;

  /**
   * @return \clever_systems\mmm2\Project
   */
  public static function get() {
    return self::$project;
  }

  /**
   * @param \clever_systems\mmm2\Project $project
   */
  public static function set($project) {
    self::$project = $project;
  }

}
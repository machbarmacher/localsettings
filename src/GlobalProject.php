<?php
/**
 * @file GlobalProject.php
 */

namespace clever_systems\mmm-builder;


class GlobalProject {
  /** @var Project */
  protected static $project;

  /**
   * @return \clever_systems\mmm-builder\Project
   */
  public static function get() {
    return self::$project;
  }

  /**
   * @param \clever_systems\mmm-builder\Project $project
   */
  public static function set($project) {
    self::$project = $project;
  }

}
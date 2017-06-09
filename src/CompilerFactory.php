<?php


namespace machbarmacher\localsettings;


class CompilerFactory {
  /** @var string */
  protected $include;
  /** @var Project */
  protected $project;
  /** @var Compiler */
  protected $compiler;

  /**
   * CompilerFactory constructor.
   * @param string|null $include
   */
  public function __construct($include) {
    if (!$include) {
      $include = '../localsettings/project.php';
    }
    $this->include = $include;
    $this->project = @include $include;
    if ($this->valid()) {
      $this->compiler = new Compiler($this->project);
    }
  }


  public function valid() {
    return (bool) $this->project;
  }

  /**
   * @return \machbarmacher\localsettings\Compiler
   */
  public function get() {
    return $this->compiler;
  }

  public function validate() {
    if (!$this->valid()) {
      return drush_set_error('DRUSH_localsettings_ERROR', dt('File not found: @file', ['@file' => $this->include]));
    }
    return TRUE;
  }

}

<?php


namespace clever_systems\mmm_builder;


use clever_systems\mmm_builder\Commands\Commands;
use clever_systems\mmm_builder\Commands\WriteFile;

class FileAssembler {
  protected $pattern;
  protected $dir_suffix = '.d';
  protected $all = 'all';
  
  /**
   * FileAssembler constructor.
   * @param $pattern
   */
  public function __construct($pattern) {
    $this->pattern = $pattern;
  }

  public function execute(Commands $commands) {
    foreach (glob($this->pattern) as $target) {
      $contents = [];
      $sources_dir = $target . $this->dir_suffix;
      if (is_file($target) && is_dir($sources_dir)) {
        $sources = glob("$sources_dir/*");
        $common_sources_dir = preg_replace('#\*#u', $this->all, $this->pattern) . $this->dir_suffix;
        $sources = array_merge($sources, glob($common_sources_dir));
        sort($sources);
        foreach ($sources as $source) {
          $contents[] = file_get_contents($source);
        }
      }
      $content = implode('', $contents);
      $commands->add(new WriteFile($target, $content));
    }
  }

}

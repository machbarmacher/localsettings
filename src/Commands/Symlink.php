<?php


namespace clever_systems\mmm_builder\Commands;


use Drush\Log\LogLevel;

abstract class Symlink extends FileOp implements CommandInterface {
  use FileExistsTrait;
  protected function doExecute() {
    if ($this->checkTargetDoesNotExist($this->filename)) {
      symlink($this->getContent(), $this->filename);
    }
  }
}

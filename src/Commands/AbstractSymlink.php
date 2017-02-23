<?php


namespace clever_systems\mmm_builder\Commands;


use Drush\Log\LogLevel;

abstract class AbstractSymlink extends AbstractFileOp implements CommandInterface {
  use FileExistsTrait;
  protected function doExecute() {
    if ($this->checkTargetDoesNotExist($this->filename)) {
      symlink($this->getContent(), $this->filename);
    }
  }
  protected function getContent() {
    $target = $this->getLinkTarget();
    return "SYMLINK<$target>";
  }

  abstract protected function getLinkTarget();

}

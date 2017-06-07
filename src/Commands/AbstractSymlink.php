<?php


namespace machbarmacher\localsettings\Commands;


abstract class AbstractSymlink extends AbstractFileOp implements CommandInterface {
  use FileExistsTrait;
  protected function doExecute() {
    if ($this->checkTargetDoesNotExist($this->filename)) {
      // Ignore warning if exists.
      @symlink($this->getLinkTarget(), $this->filename);
    }
  }
  protected function getContent() {
    $target = $this->getLinkTarget();
    return "SYMLINK<$target>";
  }

  abstract protected function getLinkTarget();

}

<?php


namespace clever_systems\mmm_builder\Commands;


abstract class AbstractWriteFile extends AbstractFileOp implements CommandInterface {
  use FileExistsTrait;
  protected function doExecute() {
    if ($this->checkTargetDoesNotExist($this->filename)) {
      file_put_contents($this->filename, $this->getContent());
    }
  }
}

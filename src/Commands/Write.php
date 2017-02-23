<?php


namespace clever_systems\mmm_builder\Commands;


abstract class Write extends FileOp implements CommandInterface {
  use FileExistsTrait;
  protected function doExecute() {
    if ($this->checkFile($this->filename)) {
      file_put_contents($this->filename, $this->getContent());
    }
  }
}

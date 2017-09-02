<?php


namespace machbarmacher\localsettings\Commands;


class MoveFile extends AbstractTwoFileOp implements CommandInterface {

  public function execute(array &$results = [], $simulate = FALSE) {
    (new CopyFile($this->source, $this->target))->execute($results, $simulate);
    (new DeleteFile($this->source))->execute($results, $simulate);
  }
  
}

<?php
namespace FreePBX\modules\Soundlang;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    /**
     * Note custom soundfiles are backed up via recordings
     */
    $files = [];
    $dirs = [];
    $configs = [];
    $sl = $this->FreePBX->Soundlang;
    $configs['packages'] = $sl->getPackages(true);
    $configs['settings'] = $sl->dumpSettings($this->FreePBX->Database);
    $this->addConfigs($configs);
    return $this;
  }
}
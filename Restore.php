<?php
namespace FreePBX\modules\Soundlang;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    $this->FreePBX->Soundlang->loadSettings($configs['settings']);
    foreach($configs['packages'] as $package){
      $this->FreePBX->Soundlang->installPackage($package['id']);
    }
  }

  public function processLegacy($pdo, $data, $tablelist, $unknowntables, $tmpdir){
    $tables = array_flip($tables + $unknownTables);
    if (!isset($tables['soundlang_settings'])) {
      $settings = $this->FreePBX->Soundlang->dumpSettings($pdo);
      $this->FreePBX->Soundlang->loadSettings($settings);
    }
    $this->FreePBX->Soundlang->setDatabase($pdo);
    $packages = $this->FreePBX->Soundlang->getPackages(true);
    $this->Soundlang->FreePBX->resetDatabase();
    foreach ($packages as $package) {
      $this->FreePBX->Soundlang->installPackage($package['id']);
    }
    return $this;
  }
}
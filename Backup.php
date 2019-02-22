<?php
namespace FreePBX\modules\Soundlang;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		/**
		 * Note custom soundfiles are backed up via recordings
		 */

		$sl = $this->FreePBX->Soundlang;
		$configs = [
			'packages' => $sl->getPackages(true),
			'settings' => $this->FreePBX->Database->query("SELECT * FROM soundlang_settings")->fetchAll(\PDO::FETCH_ASSOC),
			'customlangs' => $this->FreePBX->Database->query("SELECT * FROM soundlang_customlangs")->fetchAll(\PDO::FETCH_ASSOC)
		];
		$this->addConfigs($configs);
		return $this;
	}
}
<?php
namespace FreePBX\modules\Soundlang;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{

	public function repos_alive(){
		$connected 	= @fsockopen("sng7.com", 80); 
       	if ($connected){
        	$result	= true;
          	fclose($connected);
		}else{
			$result = false;
		}
      return $result;		
	}

	public function runRestore($jobid){
		if($this->repos_alive()){
			$configs = $this->getConfigs();
			$this->addDataToTableFromArray('soundlang_customlangs', $configs['customlangs']);
			$this->addDataToTableFromArray('soundlang_packages', $configs['packages']);
			$this->addDataToTableFromArray('soundlang_settings', $configs['settings']);

			$this->redownloadPackages();			
		}
		else{
			out( "/!\\ "._("Unable to download the some dependencies for restoring. Please enable internet access and try restoring again.")." /!\\");
		}

	}

	public function processLegacy($pdo, $data, $tablelist, $unknowntables){
		$packages = $pdo->query("SELECT * FROM soundlang_packages WHERE installed IS NOT NULL")->fetchAll(\PDO::FETCH_ASSOC);
		$this->addDataToTableFromArray('soundlang_packages', $packages);

		$settings = $pdo->query("SELECT * FROM soundlang_settings")->fetchAll(\PDO::FETCH_ASSOC);
		$this->addDataToTableFromArray('soundlang_settings', $settings);

		$customlangs = $pdo->query("SELECT * FROM soundlang_customlangs")->fetchAll(\PDO::FETCH_ASSOC);
		$this->addDataToTableFromArray('soundlang_customlangs', $customlangs);

		$this->redownloadPackages();
	}

	public function redownloadPackages() {
		$this->log(_('Getting online packages...'));
		$this->FreePBX->Soundlang->getOnlinePackages();
		foreach($this->FreePBX->Soundlang->getPackages(true) as $package) {
			$this->log(sprintf(_('Redownloading %s-%s.%s'),$package['language'],$package['module'],$package['format']));
			$this->FreePBX->Soundlang->installPackage($package['id'], true);
		}
	}
}
<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
global $db;

$first_install = db_e($db->getAll('SELECT * FROM soundlang_settings'), '');

$sql[] = 'CREATE TABLE IF NOT EXISTS `soundlang_settings` (
 `keyword` varchar(20) NOT NULL,
 `value` varchar(80) NOT NULL,
 PRIMARY KEY (`keyword`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `soundlang_customlangs` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `language` varchar(20) NOT NULL,
 `description` varchar(80) NOT NULL,
 PRIMARY KEY (`id`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `soundlang_packs` (
 `type` varchar(20) NOT NULL,
 `module` varchar(80) NOT NULL,
 `language` varchar(20) NOT NULL,
 `format` varchar(20) NOT NULL,
 `version` varchar(20) DEFAULT NULL,
 `installed` varchar(20) DEFAULT NULL,
 `timestamp` timestamp NOT NULL,
 PRIMARY KEY (`type`,`module`,`language`,`format`)
);';

$sql[] = 'CREATE TABLE IF NOT EXISTS `soundlang_prompts` (
 `type` varchar(20) NOT NULL,
 `module` varchar(80) NOT NULL,
 `language` varchar(20) NOT NULL,
 `format` varchar(20) NOT NULL,
 `filename` varchar(80) DEFAULT NULL
);';

if ($first_install) {
	$language = $db->getOne("SELECT data FROM sipsettings WHERE keyword = 'language' OR keyword = 'sip_language'");
	if (db_e($language, '')) {
		$language = "en";
	}

	$db->query("DELETE FROM sipsettings WHERE keyword = 'language' OR keyword = 'sip_language'");
	$db->query("DELETE FROM iaxsettings WHERE keyword = 'language' OR keyword = 'sip_language'");

	$sql[] = "INSERT INTO soundlang_settings (keyword, value) VALUES
			('language', '$language')
	";
}

foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx("Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

if($first_install) {
  $soundlang = \FreePBX::create()->Soundlang;
  $online = $soundlang->getOnlinePackages();
  if($online) {
    outn(_("New install, downloading default english language set..."));
    $list = $soundlang->getPackages();
    $found = false;
    foreach($list as $id => $package) {
      if($package['language'] == 'en' && $package['module'] == 'core-sounds' && $package['format'] == "ulaw") {
        $soundlang->installPackage($package);
        $found = true;
        break;
      }
    }
    if($found) {
      out(_("Done"));
    } else {
      out(_("Not Found. You will need to install languages manually in the module"));
    }
  }
}

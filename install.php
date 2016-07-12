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
	$vlsd = FreePBX::Config()->get("ASTVARLIBDIR")."/sounds";

	$online = $soundlang->getOnlinePackages();
	$alreadyinstalled = array();
	if($online) {
		out(_("New install, downloading default english language set..."));
		$list = $soundlang->getPackages();
		$found = false;
		foreach($list as $id => $package) {
			if($package['language'] == 'en' &&
				in_array($package['module'], array('core-sounds','extra-sounds')) &&
				in_array($package['format'], array("ulaw","g722"))) {

				outn(sprintf(_("Installing %s..."),$package['module']."-".$package['format']));
				$soundlang->installPackage($package);
				$allreadyinstalled[$package['module']."-".$package['format']] = true;
				out(_("Done"));
			}
		}
		out(_("Finished installing default sounds"));
	}

	// Install any packages that already exist on the system, too
	$installed = glob("$vlsd/.asterisk-*");
	foreach ($installed as $pkg) {
		if (preg_match("!/\.(asterisk.+)$!", $pkg, $out)) {
			$tmparr = explode("-", $out[1]);
			$package = array(
				"type" => $tmparr[0],
				"module" => $tmparr[1].'-'.$tmparr[2],
				"language" => $tmparr[3],
				"format" => $tmparr[4],
				"version" => $tmparr[5]
			);
			if (isset($allreadyinstalled[$package['module']."-".$package['format']])) {
				// This was already installed above
				continue;
			}
			out(sprintf(_("Installing additional package %s..."),$package['module']."-".$package['format']));
			$soundlang->installPackage($package);
		}
	}
}

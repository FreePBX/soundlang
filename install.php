<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
global $db;

$first_install = db_e($db->getAll('SELECT * FROM soundlang_settings'), '');

$m = \FreePBX::Database()->migrate('soundlang_packages');
$cols = array(
	"id" => array( "type" => "bigint", "primaryKey" => true, "autoincrement" => true, "notnull" => true ),
	"type" => array( "type" => "string", "length" => 20, "notnull" => true ),
	"module" => array( "type" => "string", "length" => 80, "notnull" => true ),
	"language" => array( "type" => "string", "length" => 20, "notnull" => true ),
	"license" => array( "type" => "blob", "notnull" => false ),
	"author" => array( "type" => "string", "length" => 80, "notnull" => false ),
	"authorlink" => array( "type" => "string", "length" => 256, "notnull" => false ),
	"format" => array( "type" => "string", "length" => 20, "notnull" => true ),
	"version" => array( "type" => "string", "length" => 20, "notnull" => false ),
	"installed" => array( "type" => "string", "length" => 20, "notnull" => false ),
);

$ind = array(
	"id" => array(
		"type" => "unique",
		"cols" => array( "id" ),
	),
	"unique" => array(
		"type" => "unique",
		"cols" => array( "type", "module", "language", "format" ),
	),
);

$m->modify($cols, $ind);

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
			('language', '$language'),
			('formats', 'ulaw,g722')
	";
} else {
	try {
		$db->query("INSERT INTO soundlang_packages (type, module, language, format, version, installed) SELECT type, module, language, format, version, installed FROM soundlang_packs");
		$db->query("DROP TABLE soundlang_packs");
	} catch (\Exception $e) {
		// Ignore errors
	}
}

foreach ($sql as $statement){
	$check = $db->query($statement);
	if (DB::IsError($check)){
		die_freepbx("Can not execute $statement : " . $check->getMessage() .  "\n");
	}
}

$soundlang = \FreePBX::create()->Soundlang;
try {
	$online = $soundlang->getOnlinePackages();
} catch(\Exception $e) {
	out(sprintf(_("Unable to get online sound packages. Error was: [%s] %s. Continuing..."),$e->getCode(), $e->getMessage()));
	$online = false;
}


if($first_install) {
	$vlsd = FreePBX::Config()->get("ASTVARLIBDIR")."/sounds";

	$alreadyinstalled = array();
	$list = array();
	if($online) {
		out(_("New install, downloading default english language set..."));
		$list = $soundlang->getPackages();
		foreach($list as $id => $package) {
			if($package['language'] == 'en' &&
				in_array($package['module'], array('core-sounds','extra-sounds','module-sounds')) &&
				in_array($package['format'], array("ulaw","g722"))) {

				outn(sprintf(_("Installing %s..."),$package['module']."-".$package['format']));
				$soundlang->installPackage($package['id']);
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
			foreach($list as $id => $p) {
				if($p['language'] == $package['language'] && $p['version'] == $package['version'] && $p['format'] == $package['format']) {
					$package['id'] = $id;
					break;
				}
			}
			if(!empty($package['id'])) {
				out(sprintf(_("Installing additional package %s..."),$package['module']."-".$package['format']));
				$soundlang->installPackage($package['id']);
			}
		}
	}
}


/* Find and install any missing packages for installed languages. */
if ($online) {
	$languages = array();
	$packages = $soundlang->getPackages();
	if (!empty($packages)) {
		foreach ($packages as $package) {
			if (!empty($package['installed'])) {
				$languages[$package['language']] = $package['language'];
			}
		}
	}

	/* Update any installed languages. */
	foreach ($languages as $language) {
		out(sprintf(_("Installing/updating packages for %s..."), $language));
		$soundlang->installLanguage($language);
		out(_("Done"));
	}
}

$o = \FreePBX::OOBE();
$c = $o->getConfig('completed');
if(!empty($c) && is_array($c)) {
	$c['soundlang'] = 'soundlang';
	$o->setConfig('completed',$c);
}

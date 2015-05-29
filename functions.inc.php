<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.

function soundlang_hookGet_config($engine) {
	global $core_conf;

	switch($engine) {
	case "asterisk":
		if (isset($core_conf) && is_a($core_conf, "core_conf")) {
			$language = FreePBX::Soundlang()->getLanguage();
			if ($language != "") {
				$core_conf->addSipGeneral('language', $language);
				$core_conf->addIaxGeneral('language', $language);
			}
		}
		break;
	}
}

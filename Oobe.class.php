<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Soundlang;

class OOBE {
	public function __construct(private $sl = false)
 {
 }

	public function oobeRequest() {
		if(isset($_POST['oobeSoundLang']) && isset($_POST['oobeGuiLang'])) {
			try {
				\FreePBX::Soundlang()->installLanguage($_POST['oobeSoundLang']);
				\FreePBX::Soundlang()->setLanguage($_POST['oobeSoundLang']);
			} catch(\Exception) {}

			\FreePBX::Config()->set_conf_values(['UIDEFAULTLANG' => $_POST['oobeGuiLang'], 'SHOWLANGUAGE' => true], true, true);
			return true;
		} else {
			$locale = set_language();
			$langlist = [];
			$langlist['en_US'] = function_exists('locale_get_display_name') ? locale_get_display_name('en_US', $locale) : 'en_US';
			foreach(glob(\FreePBX::Config()->get("AMPWEBROOT")."/admin/i18n/*",GLOB_ONLYDIR) as $langDir) {
				$lang = basename((string) $langDir);
				$langlist[$lang] = function_exists('locale_get_display_name') ? locale_get_display_name($lang, $locale) : $lang;
			}

			$langs = $this->sl->getAvailableLanguages();
			show_view(__DIR__."/views/oobe.php",["langs" => $langs, "langlist" => $langlist]);
		}
		return false;
	}
}

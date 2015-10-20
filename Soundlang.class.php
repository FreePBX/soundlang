<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Schmooze Com Inc.
//
namespace FreePBX\modules;
// Default setting array passed to ajaxRequest
$setting = array('authenticate' => true, 'allowremote' => false);

class Soundlang extends \FreePBX_Helpers implements \BMO {
	private $message = '';
	private $maxTimeLimit = 250;

	public function __construct($freepbx = null) {
		$this->db = $freepbx->Database;
		$this->FreePBX = $freepbx;
	}

	public function install() {

	}
	public function uninstall() {

	}
	public function backup(){

	}
	public function restore($backup){

	}

	public function doDialplanHook(&$ext, $engine, $priority) {
	}

	public static function myDialplanHooks() {
		return 500;
	}

	/**
	 * Function used in page.soundlang.php
	 */
	public function myShowPage() {
		$request = $_REQUEST;
		$action = !empty($request['action']) ? $request['action'] : '';

		$html .= load_view(dirname(__FILE__).'/views/main.php', array('message' => $this->message));

		$languages = $this->getLanguages();

		switch ($action) {
		case '':
		case 'save':
			$language = $this->getLanguage();

			$html .= load_view(dirname(__FILE__).'/views/select.php', array('languages' => $languages, 'language' => $language));
			break;
		case 'packages':
		case 'install':
		case 'uninstall':
			$this->getOnlinePackages();

			$packages = $this->getPackages();
			if (empty($packages)) {
				break;
			}

			usort($packages, function($a, $b) {
				/* Sort packages by type, module, language, then format. */
				if ($a['type'] == $b['type']) {
					if ($a['module'] == $b['module']) {
						if ($a['language'] == $b['language']) {
							if ($a['format'] == $b['format']) {
								return 0;
							} else {
								return ($a['format'] < $b['format']) ? -1 : 1;
							}
						} else {
							return ($a['language'] < $b['language']) ? -1 : 1;
						}
					} else {
						return ($a['module'] < $b['module']) ? -1 : 1;
					}
				} else {
					return ($a['type'] < $b['type']) ? -1 : 1;
				}
			});

			$languagenames = $this->getLanguageNames();
			$languagelocations = $this->getLocationNames();
			$html .= load_view(dirname(__FILE__).'/views/packages.php', array('packages' => $packages, 'languagenames' => $languagenames, 'languagelocations' => $languagelocations));
			break;
		case 'customlangs':
		case 'delcustomlang':
			$customlangs = $this->getCustomLanguages();

			$html .= load_view(dirname(__FILE__).'/views/customlangs.php', array('customlangs' => $customlangs));
			break;
		case 'addcustomlang':
		case 'showcustomlang':
			if ($action == 'showcustomlang' && !empty($request['customlang'])) {
				$customlang = $this->getCustomLanguage($request['customlang']);
			}

			$html .= load_view(dirname(__FILE__).'/views/customlang.php', array('customlang' => $customlang));
		}

		return $html;
	}

	/**
	 * Get Inital Display
	 * @param {string} $display The Page name
	 */
	public function doConfigPageInit($display) {
		$request = $_REQUEST;
		$action = !empty($request['action']) ? $request['action'] : '';

		switch ($action) {
		case 'save':
			$language = $request['language'];

			$this->setLanguage($language);
			break;
		case 'install':
			$package['type'] = $request['type'];
			$package['module'] = $request['module'];
			$package['language'] = $request['language'];
			$package['format'] = $request['format'];
			$package['version'] = $request['version'];

			$this->installPackage($package);

			break;
		case 'uninstall':
			$package['type'] = $request['type'];
			$package['module'] = $request['module'];
			$package['language'] = $request['language'];
			$package['format'] = $request['format'];

			$this->uninstallPackage($package);

			break;
		case 'customlangs':
		case 'showcustomlang':
			$save = $request['save'];

			if ($save == 'customlang') {
				$id = $request['customlang'];
				$language = $request['language'];
				$description = $request['description'];

				if (empty($id)) {
					$this->addCustomLanguage($language, $description);
				} else {
					$this->updateCustomLanguage($id, $language, $description);
				}
			}
			break;
		case 'delcustomlang':
			$id = $request['customlang'];
			$this->delCustomLanguage($id);
			break;
		}
	}

	/**
	 * Generate floating action bar
	 * @param  array $request the $_REQUEST
	 * @return array          Finalized button array
	 */
	public function getActionBar($request) {
		$action = !empty($request['action']) ? $request['action'] : '';

		$buttons = array();

		switch ($action) {
		case '':
		case 'save':
			$buttons['reset'] = array(
				'name' => 'reset',
				'id' => 'reset',
				'value' => _('Reset')
			);
			$buttons['submit'] = array(
				'name' => 'submit',
				'id' => 'submit',
				'value' => _('Submit')
			);
			break;
		case 'showcustomlang':
			$buttons['delete'] = array(
				'name' => 'delete',
				'id' => 'delete',
				'value' => _('Delete')
			);
			$buttons['reset'] = array(
				'name' => 'reset',
				'id' => 'reset',
				'value' => _('Reset')
			);
			$buttons['submit'] = array(
				'name' => 'submit',
				'id' => 'submit',
				'value' => _('Submit')
			);
			break;
		case 'addcustomlang':
			$buttons['reset'] = array(
				'name' => 'reset',
				'id' => 'reset',
				'value' => _('Reset')
			);
			$buttons['submit'] = array(
				'name' => 'submit',
				'id' => 'submit',
				'value' => _('Submit')
			);
			break;
		}

		return $buttons;
	}

	/**
	 * Ajax Request
	 * @param string $req     The request type
	 * @param string $setting Settings to return back
	 */
	public function ajaxRequest($req, $setting){
		switch($req){
			case "delete":
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	/**
	 * Handle AJAX
	 */
	public function ajaxHandler(){
		$request = $_REQUEST;
		switch($request['command']){
			case 'delete':
				switch ($request['type']) {
					case 'customlangs':
						$ret = array();
						foreach($request['customlangs'] as $language){
							$ret[$language] = $this->delCustomLanguage($language);
						}
						return array('status' => true, 'message' => $ret);
					break;
				}
			break;
			default:
				echo json_encode(_("Error: You should never see this"));
			break;
		}
	}

	/**
	 * Get Language Names
	 * @return array Array of language keys to Names
	 */
	public function getLanguageNames() {
		$names = array(
			'de' => _('German'),
			'en' => _('English'),
			'es' => _('Spanish'),
			'fi' => _('Finish'),
			'fr' => _('French'),
			'it' => _('Italian'),
			'ja' => _('Japanese'),
			'nl' => _('Dutch'),
			'pt' => _('Portuguese'),
			'ru' => _('Russian'),
			'sv' => _('Swedish'),
			'zh' => _('Chinese'),
		);

		return $names;
	}

	/**
	 * Get language Locales
	 * @return array Array of Language locales
	 */
	public function getLocationNames() {
		$names = array(
			'AU' => _('Australia'),
			'BE' => _('Belgium'),
			'BR' => _('Brazil'),
			'CA' => _('Canada'),
			'CH' => _('Switzerland'),
			'CN' => _('China'),
			'DE' => _('Germany'),
			'ES' => _('Spain'),
			'FI' => _('Finland'),
			'FR' => _('France'),
			'GB' => _('United Kingdom'),
			'HK' => _('Hong Kong'),
			'IT' => _('Italy'),
			'JA' => _('Japan'),
			'NL' => _('Netherlands'),
			'NZ' => _('New Zealand'),
			'MX' => _('Mexico'),
			'PT' => _('Portugal'),
			'SE' => _('Sweden'),
			'TW' => _('Taiwan'),
			'US' => _('United States'),
			'ZA' => _('South Africa'),
		);

		return $names;
	}

	/**
	 * Get Languages
	 *
	 * This gets unique languges:
	 * OUT > Array
	 * (
	 * 		[en] => English
	 *   	[en_GB] => English (United Kingdom)
	 * )
	 * @return [type] [description]
	 */
	public function getLanguages() {
		$names = $this->getLanguageNames();
		$locations = $this->getLocationNames();

		$packagelangs = array();
		$packages = $this->getPackages();
		if (!empty($packages)) {
			foreach ($packages as $package) {
				if (!empty($package['installed'])) {
					//Try to use local_get_display_name if it's installed
					if(function_exists('locale_get_display_name')) {
						$language = set_language();
						$name = locale_get_display_name($package['language'], $language);
					} else {
						$lang = explode('_', $package['language'], 2);
						if ((count($lang) == 2) && !empty($locations[$lang[1]]) && !empty($names[$lang[0]])) {
							$name = $names[$lang[0]] . ' - ' . $locations[$lang[1]];
						} else if (!empty($names[$lang[0]])) {
							$name = $names[$lang[0]];
						} else {
							$name = $lang[0];
						}
					}
					$packagelangs[$package['language']] = $name;
				}
			}
		}

		$customlangs = array();
		$customs = $this->getCustomLanguages();
		if (!empty($customs)) {
			foreach ($customs as $customlang) {
				$customlangs[$customlang['language']] = $customlang['description'];
			}
		}

		$languages = array_merge($packagelangs, $customlangs);
		if (empty($languages)) {
			$languages = array('en' => $names['en']);
		}

		asort($languages);

		return $languages;
	}

	/**
	 * Get the default global language
	 * @return string The language ID
	 */
	public function getLanguage() {
		$sql = "SELECT value FROM soundlang_settings WHERE keyword = 'language';";
		$language = $this->db->getOne($sql);

		if (empty($language)) {
			$language = 'en';
		}

		return $language;
	}

	/**
	 * Set the default language
	 * @param string $language The Language ID
	 */
	public function setLanguage($language) {
		$sql = "UPDATE soundlang_settings SET value = :language WHERE keyword = 'language';";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(':language' => $language));

		needreload();
	}

	/**
	 * Get all Custom Languages
	 * @return array Array of custom languages
	 */
	private function getCustomLanguages() {
		$customlangs = array();

		$sql = "SELECT id, language, description FROM soundlang_customlangs;";
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$languages = $sth->fetchAll(\PDO::FETCH_ASSOC);

		if (!empty($languages)) {
			foreach ($languages as $language) {
				$customlangs[] = array(
					'id' => $language['id'],
					'language' => $language['language'],
					'description' => $language['description'],
				);
			}
		}

		return $customlangs;
	}

	/**
	 * Get a custom language
	 * @param  int $id The language ID
	 * @return array     Array of information about language
	 */
	private function getCustomLanguage($id) {
		$sql = "SELECT id, language, description FROM soundlang_customlangs WHERE id = :id;";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':id' => $id,
		));
		$customlang = $sth->fetch(\PDO::FETCH_ASSOC);

		return $customlang;
	}

	/**
	 * Update Custom Language information
	 * @param  int $id          The language ID
	 * @param  string $language    The language type
	 * @param  string $description The language description
	 */
	private function updateCustomLanguage($id, $language, $description = '') {
		global $amp_conf;

		$sql = "UPDATE soundlang_customlangs SET language = :language, description = :description WHERE id = :id";
		$sth = $this->db->prepare($sql);
		$res = $sth->execute(array(
			':id' => $id,
			':language' => $language,
			':description' => $description,
		));

		$destdir = $amp_conf['ASTVARLIBDIR'] . "/sounds/" . str_replace('/', '_', $language) . "/";
		@mkdir($destdir);
	}

	/**
	 * Add a new custom language
	 * @param string $language    The language type
	 * @param string $description The language description
	 */
	private function addCustomLanguage($language, $description = '') {
		global $amp_conf;

		$sql = "INSERT INTO soundlang_customlangs (language, description) VALUES (:language, :description)";
		$sth = $this->db->prepare($sql);
		$res = $sth->execute(array(
			':language' => $language,
			':description' => $description,
		));

		$destdir = $amp_conf['ASTVARLIBDIR'] . "/sounds/" . str_replace('/', '_', $language) . "/";
		@mkdir($destdir);
	}

	/**
	 * Delete custom language
	 * @param  int $id The language ID
	 */
	private function delCustomLanguage($id) {
		global $amp_conf;

		$language = $this->getCustomLanguage($id);
		if ($language['language'] == $this->getLanguage()) {
			/* Our current language was removed.  Fall back to default. */
			$this->setLanguage('en');
		}

		$sql = "DELETE FROM soundlang_customlangs WHERE id = :id;";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':id' => $id,
		));

		$destdir = $amp_conf['ASTVARLIBDIR'] . "/sounds/" . str_replace('/', '_', $language['language']) . "/";
		@rmdir($destdir);
	}

	/**
	 * Get information about an installed package
	 * @param  array $package Array of information about package
	 * @return mixed          The installed version or null
	 */
	private function getPackageInstalled($package) {
		$sql = "SELECT * FROM soundlang_packs WHERE type = :type AND module = :module AND language = :language AND format = :format";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':type' => $package['type'],
			':module' => $package['module'],
			':language' => $package['language'],
			':format' => $package['format'],
		));
		$installed = $sth->fetch(\PDO::FETCH_ASSOC);

		return !empty($installed) ? $installed['installed'] : NULL;
	}

	/**
	 * Set package installed information
	 * @param array $package   Array of information about the package
	 * @param string $installed the new version to set
	 */
	private function setPackageInstalled($package, $installed) {
		$sql = "UPDATE soundlang_packs SET installed = :installed WHERE type = :type AND module = :module AND language = :language AND format = :format";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':installed' => $installed,
			':type' => $package['type'],
			':module' => $package['module'],
			':language' => $package['language'],
			':format' => $package['format'],
		));
	}

	/**
	 * Get list of all locally known packages
	 * @return array Array of package information(s)
	 */
	public function getPackages() {
		$sql = "SELECT * FROM soundlang_packs";
		$sth = $this->db->prepare($sql);
		$sth->execute();

		$packages = $sth->fetchAll(\PDO::FETCH_ASSOC);
		return $packages;
	}

	/**
	 * Get online packages
	 * @return array Array of packages
	 */
	public function getOnlinePackages() {
		$version = getversion();
		// we need to know the freepbx major version we have running (ie: 12.0.1 is 12.0)
		preg_match('/(\d+\.\d+)/',$version,$matches);
		$base_version = $matches[1];

		$xml = $this->getRemoteFile("/sounds-" . $base_version . ".xml");
		if(!empty($xml)) {
			$soundsobj = simplexml_load_string($xml);

			/* Convert to an associative array */
			$sounds = json_decode(json_encode($soundsobj), true);
			if (empty($sounds) || empty($sounds['sounds']) || empty($sounds['sounds']['package'])) {
				return false;
			}

			$available = $sounds['sounds']['package'];

			/* Delete packages that aren't installed */
			$sql = "DELETE FROM soundlang_packs WHERE installed IS NULL";
			$sth = $this->db->prepare($sql);
			$sth->execute();

			/* Add / Update package versions */
			$sql = "INSERT INTO soundlang_packs (type, module, language, format, version) VALUES (:type, :module, :language, :format, :version) ON DUPLICATE KEY UPDATE version = :version";
			$sth = $this->db->prepare($sql);
			foreach ($available as $package) {
				$res = $sth->execute(array(
					':type' => $package['type'],
					':module' => $package['module'],
					':language' => $package['language'],
					':format' => $package['format'],
					':version' => $package['version'],
				));
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Install Package from online servers
	 * @param  array $package Array of information about the package
	 * @return mixed          return a string of the installed package or null
	 */
	public function installPackage($package) {
		global $amp_conf;

		$this->uninstallPackage($package);

		$tmpdir = sys_get_temp_dir();
		$pkgdir = $tmpdir . '/' . $package['type'] . '-' . $package['module'] . '-' . $package['language'] . '-' . $package['format'] . '-' . $package['version'] . '/';

		$filename = $package['type'] . '-' . $package['module'] . '-' . $package['language'] . '-' . $package['format'] . '-' . $package['version'] . '.tar.gz';

		$filedata = $this->getRemoteFile("/sounds/" . $filename);
		file_put_contents($tmpdir . "/" . $filename, $filedata);

		/* Untar into temp dir */
		@mkdir($pkgdir);
		exec("tar zxf " . $tmpdir . "/" . escapeshellarg($filename) . " -C " . escapeshellarg($pkgdir), $output, $exitcode);
		if ($exitcode != 0) {
			@rmdir($pkgdir);
			freepbx_log(FPBX_LOG_ERROR, sprintf(_("failed to open %s sounds archive."), $filename));
			return array(sprintf(_('Could not untar %s to %s'), $filename, $amp_conf['ASTVARLIBDIR'] . "/sounds/" . $package['language'] . "/"));
		}

		/* Track installed sounds */
		$olddir = getcwd();
		chdir($pkgdir);
		$glob = glob("{*.[a-z]*,*/*.[a-z]*}", GLOB_BRACE);
		$files = array_filter($glob, function($v) {
			return substr($v, -4) != ".txt";
		});
		chdir($olddir);

		if ($files && !empty($files)) {
			$sql = "INSERT INTO soundlang_prompts (type, module, language, format, filename) VALUES (:type, :module, :language, :format, :filename)";
			$sth = $this->db->prepare($sql);
			foreach ($files as $file) {
				$row = array(
					':type' => $package['type'],
					':module' => $package['module'],
					':language' => $package['language'],
					':format' => $package['format'],
					':filename' => $file,
				);
				$res = $sth->execute($row);
			}

			/* Move prompts into place */
			$destdir = $amp_conf['ASTVARLIBDIR'] . "/sounds/" . $package['language'] . "/";
			@mkdir($destdir);
			foreach ($files as $file) {
				if (!is_dir(dirname($destdir . $file))) {
					@mkdir(dirname($destdir . $file));
				}

				rename($pkgdir . $file, $destdir . $file);
			}

			$this->setPackageInstalled($package, $package['version']);

			needreload();
		}

		if (unlink($tmpdir . "/" . $filename) === false) {
			freepbx_log(FPBX_LOG_WARNING, sprintf(_("failed to delete %s from cache directory after opening sounds archive."), $filename));
		}
	}

	/**
	 * Uninstall a package
	 * @param  array $package Information about the package
	 */
	public function uninstallPackage($package) {
		global $amp_conf;

		$this->setPackageInstalled($package, NULL);

		$sql = "SELECT * FROM soundlang_prompts WHERE type = :type AND module = :module AND language = :language AND format = :format";
		$sth = $this->db->prepare($sql);
		$sth->execute(array(
			':type' => $package['type'],
			':module' => $package['module'],
			':language' => $package['language'],
			':format' => $package['format'],
		));
		$files = $sth->fetchAll(\PDO::FETCH_ASSOC);

		if ($files) {
			$destdir = $amp_conf['ASTVARLIBDIR'] . "/sounds/" . $package['language'] . "/";
			foreach ($files as $file) {
				@unlink($destdir . $file['filename']);
			}

			/* Purge installed prompts */
			$sql = "DELETE FROM soundlang_prompts WHERE type = :type AND module = :module AND language = :language AND format = :format";
			$sth = $this->db->prepare($sql);
			$sth->execute(array(
				':type' => $package['type'],
				':module' => $package['module'],
				':language' => $package['language'],
				':format' => $package['format'],
			));

			needreload();
		}
	}

	/**
	 * Retrieve a remote file
	 * Stores file into memory
	 * @param  string $path The full path to said file
	 * @return string       binary representation of file
	 */
	private function getRemoteFile($path) {
		$modulef =& \module_functions::create();

		$contents = null;

		$mirrors = $modulef->generate_remote_urls($path, true);

		$params = $mirrors['options'];
		$params['sv'] = 2;

		foreach($mirrors['mirrors'] as $url) {
			set_time_limit($this->maxTimeLimit);

			try{
				$pest = \FreePBX::Curl()->pest($url);
				$contents = $pest->post($url . $path, $params);
				if (isset($pest->last_headers['x-regenerate-id'])) {
					$modulef->_regenerate_unique_id();
				}
				if (!empty($contents)) {
					return $contents;
				}
			} catch (\Exception $e) {
				freepbx_log(FPBX_LOG_ERROR, sprintf(_('Failed to get remote file, error was:'), (string)$e->getMessage()));
			}
		}
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Schmooze Com Inc.
//
namespace FreePBX\modules;
use splitbrain\PHPArchive\Tar;
use splitbrain\PHPArchive\Zip;
class Soundlang extends \FreePBX_Helpers implements \BMO {
	private string $message = '';
	private int $maxTimeLimit = 250;
	private readonly string $path_temp;
	private readonly string $path_sounds;
	private string $default_lang = 'en';
	private array $default_formats = ['g722', 'ulaw'];
	/** Extensions to show in the convert to section
	 * Limited on purpose because there are far too many,
	 * Most of which are not supported by asterisk
	 */
	private array $convert = ["wav", "sln", "sln16", "sln48", "g722", "ulaw", "alaw", "g729", "gsm"];
	
	private array $tables = ["packages" 	  => "soundlang_packages", "prompts"  	  => "soundlang_prompts", "settings" 	  => "soundlang_settings", "customlangs" => "soundlang_customlangs"];

	public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		$this->module_name 	= join('', array_slice(explode('\\', self::class), -1));
		$this->db 			= $freepbx->Database;
		$this->config 		= $freepbx->Config;
		$this->logger		= $freepbx->Logger()->getDriver('freepbx');
		$this->FreePBX 		= $freepbx;
		$this->path_sounds	= $this->config->get("ASTVARLIBDIR")."/sounds";
		$this->path_temp 	= $this->config->get("ASTSPOOLDIR") . "/tmp";
		if(!file_exists($this->path_temp))
		{
			mkdir($this->path_temp, 0777, true);
		}
	}

	public function install() {

	}
	public function uninstall() {

	}

	public function oobeHook() {
		include __DIR__.'/Oobe.class.php';
		$o = new Soundlang\OOBE($this);
		return $o->oobeRequest();
	}

	public function doDialplanHook(&$ext, $engine, $priority) {
		global $core_conf;
		$language = $this->getLanguage();
		if ($language != "") {
			$ext->addGlobal('SIPLANG',$language);
			$core_conf->addSipGeneral('language', $language);
			$core_conf->addIaxGeneral('language', $language);
		}
	}

	public static function myDialplanHooks() {
		return 500;
	}

	public function getRightNav($request) {
		return load_view(__DIR__.'/views/rnav.php',[]);
	}

	/**
	 * Function used in page.soundlang.php
	 */
	public function myShowPage() {
		$request = $_REQUEST;
		$action = !empty($request['action']) ? $request['action'] : '';

		$html = load_view(__DIR__.'/views/main.php', ['message' => $this->message]);

		switch ($action) {
		case 'settings':
			$formatpref = $this->getFormatPref();

			$packages = $this->getPackages();
			if (empty($packages)) {
				$formatlist = $formatpref;
			} else {
				$formatlist = [];
				foreach ($packages as $package) {
					$formatlist[$package['format']] = $package['format'];
				}
			}

			$displayvars = ['languages'  => $this->getLanguages(), 'language' 	 => $this->getLanguage(), 'formatpref' => $formatpref, 'formatlist' => $formatlist];
			$html .= load_view(__DIR__.'/views/settings.php', $displayvars);
			break;

		case '':
		case 'packages':
			$html .= load_view(__DIR__.'/views/packages.php');
			break;

		case 'customlangs':
		case 'delcustomlang':
			$customlangs = $this->getCustomLanguages();
			$html .= load_view(__DIR__.'/views/customlangs.php', ['customlangs' => $customlangs]);
			break;
		case 'addcustomlang':
		case 'showcustomlang':
			if ($action == 'showcustomlang' && !empty($request['customlang'])) {
				$customlang = $this->getCustomLanguage($request['customlang']);
			}

			$media = $this->FreePBX->Media();
			$supported = $media->getSupportedFormats();
			ksort($supported['in']);
			ksort($supported['out']);
			$supported['in']['tgz'] = 'tgz';
			$supported['in']['gz'] = 'gz';
			$supported['in']['tar'] = 'tar';
			$supported['in']['zip'] = 'zip';
			$convertto = array_intersect($supported['out'], $this->convert);

			$html .= load_view(__DIR__.'/views/customlang.php', ['customlang' => $customlang, 'convertto' => $convertto, 'supported' => $supported]);
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

		$buttons = [];

		switch ($action) {
		case 'settings':
			$buttons['reset'] = ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')];
			$buttons['submit'] = ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')];
			break;
		case 'showcustomlang':
			$buttons['delete'] = ['name' => 'delete', 'id' => 'delete', 'value' => _('Delete')];
			$buttons['reset'] = ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')];
			$buttons['submit'] = ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')];
			break;
		case 'addcustomlang':
			$buttons['reset'] = ['name' => 'reset', 'id' => 'reset', 'value' => _('Reset')];
			$buttons['submit'] = ['name' => 'submit', 'id' => 'submit', 'value' => _('Submit')];
			break;
		}

		return $buttons;
	}

	/**
	 * Ajax Request
	 * @param string $req     The request type
	 * @param string $setting Settings to return back
	 */
	public function ajaxRequest($req, &$setting){
		return match ($req) {
      "convert", "upload", "delete", "saveCustomLang", "install", "uninstall", "licenseText", "deletetemps", "oobe", "updatepackages", "packages", "packagesLang", "installlang", "updatelang", "savesettings" => true,
      default => false,
  };
	}

	public function ajaxCustomHandler()
	{
		$request = $_REQUEST;
		$command = empty($request['command']) ? '' : $request['command'];

		$isExitCode   = false;
		$isNeedreload = false;
		$showBtnClose = false;
		$msg_end	  = "";
		$msg_error	  = "";
		
		$out_top   		=	'
			<div id="langBoxContents">
				<h4>%s</h4>
				<div id="langprogress">
		';
		$out_down  		= "</div><br>";
		$out_step  		= '<i class="fa fa-angle-double-right" aria-hidden="true"></i> %s';
		$out_error 		= '<span class="error"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> %s</span><br/>';
		$out_return_ok 	= sprintf('<span class="success"> %s</span><br/>',  _('Ok!'));
		$out_return_err	= sprintf('<span class="error"> %s</span><br/>', _('Error!'));

		// Code commun to multiple commands
		if (in_array($command, ['updatelang', 'installlang']))
		{
			header('Content-type: application/octet-stream');
			// Turn off output buffering
			ini_set('output_buffering', 'off');
			// Turn off PHP output compression
			ini_set('zlib.output_compression', false);
			// Implicitly flush the buffer(s)
			ini_set('implicit_flush', true);
			ob_implicit_flush(true);
			// Clear, and turn off output buffering
			while (ob_get_level() > 0) {
				// Get the curent level
				$level = ob_get_level();
				// End the buffering
				ob_end_clean();
				// If the current level has not changed, abort
				if (ob_get_level() == $level) break;
			}
			// Disable apache output buffering/compression
			if (function_exists('apache_setenv')) {
				apache_setenv('no-gzip', '1');
				apache_setenv('dont-vary', '1');
			}

			$isExitCode   = true;
			$isNeedreload = true;
			$showBtnClose = true;

			@ ob_flush();
			flush();
		}

		switch($command)
		{
			case "updatelang":
				$languages = [];
				$packages  = $this->getPackages();
				$formats   = $this->getFormatPref();
				$isRemove  = false;
				$msg_end   = _("Update completed.");

				echo sprintf($out_top, _("Wait while languages ​​are updated"));

				if (!empty($packages))
				{
					foreach ($packages as $package)
					{
						if (!empty($package['installed']))
						{
							if (!in_array($package['format'], $formats))
							{
								$isRemove = true;
								/* Remove packages for unused formats. */
								echo sprintf($out_step, sprintf(_("Removed the [%s] format of the language [%s]..."), $package['format'], $package['language']));
								echo $this->uninstallPackage($package['id']) ? $out_return_ok : $out_return_err;
							}
							$languages[$package['language']] = true;
						}
					}
				}

				echo $isRemove ? "<br>" : "";

				if (!empty($languages))
				{
					foreach ($languages as $key => $val)
					{
						echo sprintf($out_step, sprintf(_("Updating the language [%s]..."), $key));
						/* Install any missing formats. */
						echo $this->installLanguage($key) ? $out_return_ok : $out_return_err;
					}
				}	
				
				echo $out_down;
			break;

			case "installlang":
				$lang 		= empty($request['lang']) ? "" : $request['lang'];
				$ls_package = [];
				$error 		= false;
				$error_msg 	= "";
				$msg_end 	= _("Installation completed.");

				if (empty($lang))
				{
					$error 		= true;
					$error_msg 	= _('Error: No language specified!');
				}
				else
				{
					$packages = $this->getPackages();
					if (empty($packages))
					{
				 		$error 		= true;
						$error_msg 	= _('Error: No languages ​​detected to install!');
					}
					else
					{
						$formats = $this->getFormatPref();
						foreach ($packages as $package)
						{
							if ($package['language'] == $lang)
							{
								if (empty($package['installed']) || version_compare($package['version'], $package['installed'], 'gt'))
								{
									if (in_array($package['format'], $formats))
									{
										$ls_package[] = $package['id'];
									}
								}
							}
						}
					}
				}

				if ($error)
				{
					$msg_error 	  = $error_msg;
					$msg_end 	  = "";
					$isNeedreload = false;
				}
				else
				{
					echo sprintf($out_top, sprintf(_("Wait while the language [%s] is installed"), $lang));

					if (empty($ls_package))
					{
						echo '<span class="success">'._('Everything is installed and update.').'</span><br/>';
					}
					else
					{
						foreach ($ls_package as $package_id)
						{
							$package = $this->getPackageById($package_id);

							echo "<b>".sprintf( _("Installing module [%s]:"), $package['module'])."</b><br>";

							$basename = $package['type'].'-'.$package['module'].'-'.$package['language'].'-'.$package['format'] .'-'.$package['version'];
							$soundsdir = $this->path_sounds;

							// Does this sound language package already exist on this machine?
							$txtfile = $soundsdir.'/'.$package['language'].'/'.$package['module'].'-'.$package['language'].'.txt';

							if (!file_exists("$soundsdir/.$basename") || !file_exists($txtfile))
							{
								// No. We need to fetch it.

								$tmpdir = "$soundsdir/tmp";
								if (!is_dir($tmpdir)) {
									mkdir($tmpdir);
								}

								// This is the file we want to download
								echo sprintf($out_step, sprintf(_("Downloading [%s]..."), $package['format']));
								$filename = $basename . '.tar.gz';
								$filedata = $this->getRemoteFile("/sounds/" . $filename);
								file_put_contents($tmpdir . "/" . $filename, $filedata);
								echo $out_return_ok;

								// Extract it to the correct location
								echo sprintf($out_step, sprintf(_("Extracting [%s]..."), $package['format']));
								$destdir = "$soundsdir/".$package['language']."/";
								@mkdir($destdir);

								$file_tar = sprintf("%s/%s", $tmpdir, $filename);
								try
								{
									$tar = new \PharData($file_tar);
									$tar->extractTo($destdir, null, true); // extraer todos los ficheros y sobrescribirlos
									echo $out_return_ok;
								}
								catch (\Exception $e)
								{
									$msg_exception = sprintf(_('Error: %s'), $e->getMessage());
									$this->logger->error( sprintf("%s->%s - %s", $this->module_name, __FUNCTION__, $msg_exception));
									echo $out_return_err;
									echo sprintf($out_error, $msg_exception);
									echo "<br>";
									continue;
								}
								
								//https://issues.freepbx.org/browse/FREEPBX-14426
								$txtfilenoext = $soundsdir.'/'.$package['language'].'/'.$package['module'].'-'.$package['language'];
								if(!file_exists($txtfile) && file_exists($txtfilenoext))
								{
									//missing .txt
									rename($txtfilenoext, $txtfile);
								}

								// If the txt file doesn't exist, there's something wrong with the package.
								if (!file_exists($txtfile))
								{
									$msg_exception = sprintf(_("Error: Couldn't find [%s] - not in archive [%s]?"), $txtfile, $filename);
									$this->logger->error( sprintf("%s->%s - %s", $this->module_name, __FUNCTION__, $msg_exception));
									echo sprintf($out_error, $msg_exception);
									echo "<br>";
									continue;
								}

								// Create our version file so we know it exists in the future.
								touch ("$soundsdir/.$basename");
							}

							// Get a list of sounds in this package.
							$prompts = file($txtfile, \FILE_SKIP_EMPTY_LINES);
							$files 	 = [];
							foreach ($prompts as $prompt)
							{
								// If it's a comment, skip
								if ($prompt[0] == ";") {
									continue;
								}
								// Ignore the description
								$tmparr  = explode(":", (string) $prompt);
								$files[] = $tmparr[0];
							}

							if (!$files)
							{
								$msg_exception = sprintf(_("Unable to find any soundfiles in [%s] package!"), $basename);
								$this->logger->error( sprintf("%s->%s - %s", $this->module_name, __FUNCTION__, $msg_exception));
								echo sprintf($out_error, $msg_exception);
								echo "<br>";
								continue;
							}

							echo sprintf($out_step, sprintf(_("Updating data in database [%s]..."), $package['format']));
							$row = [':type' 	=> $package['type'], ':module' 	=> $package['module'], ':language' => $package['language'], ':format' 	=> $package['format']];

							// Delete any prompts from this package previously
							$sql = sprintf("DELETE FROM `%s` WHERE `type`=:type AND `module`=:module AND `language`=:language AND `format`=:format", $this->tables['prompts']);
							$del = $this->db->prepare($sql);
							$del->execute($row);

							// Now load in the new files
							$sql = sprintf("INSERT INTO %s (type, module, language, format, filename) VALUES (:type, :module, :language, :format, :filename)", $this->tables['prompts']);
							$sth = $this->db->prepare($sql);
							foreach ($files as $file)
							{
								$row['filename'] = sprintf("%s.%s", $file, $package['format']);
								$sth->execute($row);
							}

							$this->setPackageInstalled($package, $package['version']);

							echo $out_return_ok;
							echo "<br>";
						}
					}

					echo $out_down;
				}
			break;
		}
		
		if (!empty($msg_error))
		{
			echo '<div id="langBoxContents">';
			echo '	<div id="langprogress">';
			echo '		<span class="error">' . $msg_error . '</span><br/>';
			echo "	</div>";
			echo "</div>";
		}

		if (!empty($msg_end)) {
			echo $msg_end."<br><br>";
		}

		if ($showBtnClose) {
			echo "<hr /><br />";
			echo '<button class="btn btn-success btn-block" onclick="parent.close_module_actions();" >'._("Close").'</button>';
			echo "<br><br>";
		}
		
		if ($isNeedreload) {
			needreload();
		}

		if ($isExitCode) {
			exit();
		}
	}

	/**
	 * Handle AJAX
	 */
	public function ajaxHandler(){
		$request = $_REQUEST;
		switch($request['command'])
		{
			// Ajax custom commands
			case "installlang":
			case "updatelang":
				exit();
			break;

			case "updatepackages":
				$data_return = ['status'  => true, 'message' => _("Data update completed successfully.")];
				try
				{
					if (! $this->getOnlinePackages())
					{
						$data_return['status']  = false;
						$data_return['message'] = _("Error: No data fetched from server!");
					}
				}
				catch(\Exception $e)
				{
					$data_return['status']  = false;
					$data_return['message'] = sprintf(_("Unable to get online sound packages. Error was: [%s] %s"), $e->getCode(), $e->getMessage());
				}
				return $data_return;

			case "packages":
				$installedOnly 	 = !empty($request['showall']) && strtoupper((string) $request['showall']) == "YES" ? false : true;
				$returnTableData = !empty($request['tabledata']) && strtoupper((string) $request['tabledata']) == "YES" ? true : false;

				$data_return = ['status'  => true, 'message' => ""];
				if ($data_return['status'])
				{
					$languagenames 		= $this->getLanguageNames();
					$languagelocations 	= $this->getLocationNames();
					$packages 			= $this->getPackages($installedOnly);
					$formats  			= $this->getFormatPref();
					$languages 			= [];

					foreach ($packages as $package)
					{
						if (isset($languages[$package['language']]))
						{
							$language = $languages[$package['language']];
						}
						else
						{
							$name = $package['language'];
							$lang = explode('_', (string) $name, 2);
							if (count($lang) > 1 && !empty($languagelocations[$lang[1]]) && !empty($languagenames[$lang[0]]))
							{
								$name = sprintf("%s - %s (%s)", $languagenames[$lang[0]], $languagelocations[$lang[1]], $name);
							}
							else if (!empty($languagenames[$lang[0]]))
							{
								$name = sprintf("%s (%s)", $languagenames[$lang[0]], $name);
							}
							$language = [
           'name'		 => $name,
           'lang'		 => $package['language'],
           'author' 	 => $package['author'],
           'authorlink' => $package['authorlink'],
           'license' 	 => $package['license'],
           'version_o'  => $package['version'],
           //Online
           'version_i'	 => "",
           //Intalled
           'isUpdated'  => false,
           'installed'  => false,
       ];
						}

						if (in_array($package['format'], $formats))
						{
							if (! is_null($package['installed']) && $language['version_i'] < $package['installed'] )
							{
							 	$language['version_i'] = $package['installed'];
							}
							if (! empty($package['installed']))
							{
								$language['installed'] = true;
							}
							if (! empty($package['installed']) && $package['installed'] < $package['version'])
							{
								$language['isUpdated'] = true;
							}
						}

						$languages[$package['language']] = $language;
					}
					ksort($languages);
					$data_return['data'] = ['languages' 		=> $languages, "languagenames" 	=> $languagenames, "languagelocations" => $languagelocations];
					if ($returnTableData == true)
					{
						$data_return = array_values($languages);
					}
				}
				return $data_return;

			case "packagesLang":
				$data_return = [];
				$language 	 = empty($request['lang']) ? "" : $request['lang'];
				if (! empty($language))
				{
					$packages = $this->getPackages();
					if (! empty($packages))
					{
						foreach ($packages as $package)
						{
							if ($package['language'] == $language)
							{
								$data_return[] = ["module" 	=> $package['module'], "format" 	=> $package['format'], "version" 	=> $package['version'], "installed" => is_null($package['installed']) ? "" : $package['installed'], "package" 	=> $package];
							}
						}	
					}
				}
				return $data_return;
			break;
			
			case "oobe":
				set_time_limit(0);
				return ["status" => true];
			break;

			case "install":
				$lang = empty($request['lang']) ? "" : $request['lang'];
				if (empty($lang))
				{
					$status = false;
					$message = _("No language specified!");
				}
				else
				{
					$status  = $this->installLanguage($request['lang']);
					$message = $status ? _("Installation completed successfully.") : _("Failed installation process!");
				}		
				return ["status" => $status, "message" => $message];

			case "uninstall":
				$lang = empty($request['lang']) ? "" : $request['lang'];
				if (empty($lang))
				{
					$status = false;
					$message = _("No language specified!");
				}
				else
				{
					$status  = $this->uninstallLanguage($request['lang']);
					$message = $status ? _("Uninstall completed successfully.") : _("Uninstall process failed!");
				}		
				return ["status" => $status, "message" => $message];

			case "licenseText":
				$lang = empty($request['lang']) ? "" : $request['lang'];
				if (empty($lang))
				{
					$status = false;
					$message = _("No language specified!");
				}
				else
				{
					$out = $this->getLanguageLicense($lang);
					if(is_string($out))
					{
						$status  = true;
						$license = $out;
					}
					else
					{
						$status = $out;
					}
				}
				$data_return = ["status" => $status];
				if (isset($message)) { $data_return['message'] = $message; }
				if (isset($license)) { $data_return['license'] = $license; }
				return $data_return;

			case "savesettings":
				$language 	= empty($request['language']) 	? $this->default_lang    : $request['language'];
				$formats  	= empty($request['formats'])  	? $this->default_formats : $request['formats'];
				$runinstall = empty($request['runinstall']) ? false : true;

				if (empty($language))
				{
					$data_return = ['status'  => false, 'message' => _("No language specified!")];
				}
				elseif (empty($formats))
				{
					$data_return = ['status'  => false, 'message' => _("No formats specified!")];
				}
				else
				{
					$this->setLanguage($language);
					$this->setFormatPref($formats);

					if ($runinstall)
					{
						$languages = [];
						$packages  = $this->getPackages();
						if (!empty($packages))
						{
							foreach ($packages as $package)
							{
								if (!empty($package['installed']))
								{
									if (!in_array($package['format'], $formats))
									{
										/* Remove packages for unused formats. */
										$this->uninstallPackage($package['id']);
									}
									$languages[$package['language']] = true;
								}
							}
						}
	
						if (!empty($languages))
						{
							foreach ($languages as $key => $val)
							{
								/* Install any missing formats. */
								$this->installLanguage($key);
							}
						}
					}
					
					$data_return = ['status'  	 => true, 'message' 	 => _("Successful Update."), 'runinstall' => $runinstall];
				}
				return $data_return;
			
			case "saveCustomLang":
				if (empty($_POST['id'])) {
					$this->addCustomLanguage($_POST['language'], $_POST['description']);
				} else {
					$this->updateCustomLanguage($_POST['id'], $_POST['language'], $_POST['description']);
				}
				return ["status" => true];
			break;
			case "deletetemps":
				$temps = $_POST['temps'];
				foreach($temps as $temporary) {
					$temporary = str_replace("..","",(string) $temporary);
					$temporary = $this->path_temp."/".$temporary;
					if(!file_exists($temporary)) {
						@unlink($temporary);
					}
				}
				return ["status" => true];
			break;
			case "convert":
				set_time_limit(0);
				$media = $this->FreePBX->Media;
				$temporary = $_POST['temporary'];
				$temporary = str_replace("..","",(string) $temporary);
				$temporary = $this->path_temp."/".$temporary;
				$name = basename((string) $_POST['name']);
				$codec = $_POST['codec'];
				$lang = $_POST['language'];
				$directory = $_POST['directory'];
				$path = $this->path_sounds . "/" . $lang;
				if(!empty($directory)) {
					$path = $path ."/".$directory;
				}
				if(!file_exists($path)) {
					if(!@mkdir($path)){
						$error = error_get_last();
						return ["status" => false, "message" => _("The file is not formatted correctly. Please try again...")];
					}
				}
				$name = preg_replace("/\s+|'+|`+|\"+|<+|>+|\?+|\*|\.+|&+/","-",$name);
				if(!empty($codec)) {
					$media->load($temporary);
					try {
						$media->convert($path."/".$name.".".$codec);
						//unlink($temporary);
					} catch(\Exception $e) {
						return ["status" => false, "message" => $e->getMessage()." [".$path."/".$name.".".$codec."]"];
					}
					return ["status" => true, "name" => $name];
				} else {
					$ext = pathinfo($temporary,PATHINFO_EXTENSION);
					if($temporary && file_exists($temporary)) {
						rename($temporary, $path."/".$name.".".$ext);
						return ["status" => true, "name" => $name];
					} else {
						return ["status" => true, "name" => $name];
					}
				}
			break;
			case 'delete':
				switch ($request['type']) {
					case 'customlangs':
						$ret = [];
						foreach($request['customlangs'] as $language){
							$ret[$language] = $this->delCustomLanguage($language);
						}
						return ['status' => true, 'message' => $ret];
					break;
				}
			break;
			case "upload":
				if(empty($_FILES["files"])) {
					return ["status" => false, "message" => _("No files were sent to the server")];
				}
				foreach ($_FILES["files"]["error"] as $key => $error) {
					switch($error) {
						case UPLOAD_ERR_OK:
							$extension = pathinfo((string) $_FILES["files"]["name"][$key], PATHINFO_EXTENSION);
							$extension = strtolower($extension);
							$supported = $this->FreePBX->Media->getSupportedFormats();
							$archives = ["tgz", "gz", "tar", "zip"];
							if(in_array($extension,$supported['in']) || in_array($extension,$archives)) {
								$tmp_name = $_FILES["files"]["tmp_name"][$key];
								$dname = \Media\Media::cleanFileName($_FILES["files"]["name"][$key]);
								$dname = pathinfo((string) $dname,PATHINFO_FILENAME);
								$id = time().random_int(1,1000);
								$name = $dname . '-' . $id . '.' . $extension;
								move_uploaded_file($tmp_name, $this->path_temp."/".$name);
								$gfiles = $bfiles = [];
								if(in_array($extension,$archives)) {
									//this is an archive
									if($extension == "zip") {
										$tar = new Zip();
									} else {
										$tar = new Tar();
									}
									$archive = $this->path_temp."/".$name;
									$tar->open($archive);
									$path = $this->path_temp."/".$id;
									if(!file_exists($path)) {
										mkdir($path);
									}
									$tar->extract($path);
									$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
									foreach($objects as $name => $object){
										if($object->isDir()) {
											continue;
										}
										$file = (string)$object;
										$extension = pathinfo($file, PATHINFO_EXTENSION);
										$extension = strtolower($extension);
										$dir = dirname(str_replace($path."/","",$file));
										$dir = ($dir != ".") ? $dir : "";
										$dname = \Media\Media::cleanFileName(pathinfo($file,PATHINFO_FILENAME));
										if(!in_array($extension,$supported['in'])) {
											$bfiles[] = ["directory" => $dir, "filename" => (!empty($dir) ? $dir."/" : "").$dname, "localfilename" => str_replace($this->path_temp,"",$file), "id" => ""];
											continue;
										}
										$gfiles[] = ["directory" => $dir, "filename" => (!empty($dir) ? $dir."/" : "").$dname, "localfilename" => str_replace($this->path_temp,"",$file), "id" => ""];
									}
									unlink($archive);
								} else {
									$gfiles[] = ["directory" => "", "filename" => pathinfo($dname,PATHINFO_FILENAME), "localfilename" => $name, "id" => $id];
								}
								return ["status" => true, "gfiles" => $gfiles, "bfiles" => $bfiles];
							} else {
								return ["status" => false, "message" => _("Unsupported file format")];
								break;
							}
						break;
						case UPLOAD_ERR_INI_SIZE:
							return ["status" => false, "message" => _("The uploaded file exceeds the upload_max_filesize directive in php.ini")];
						break;
						case UPLOAD_ERR_FORM_SIZE:
							return ["status" => false, "message" => _("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form")];
						break;
						case UPLOAD_ERR_PARTIAL:
							return ["status" => false, "message" => _("The uploaded file was only partially uploaded")];
						break;
						case UPLOAD_ERR_NO_FILE:
							return ["status" => false, "message" => _("No file was uploaded")];
						break;
						case UPLOAD_ERR_NO_TMP_DIR:
							return ["status" => false, "message" => _("Missing a temporary folder")];
						break;
						case UPLOAD_ERR_CANT_WRITE:
							return ["status" => false, "message" => _("Failed to write file to disk")];
						break;
						case UPLOAD_ERR_EXTENSION:
							return ["status" => false, "message" => _("A PHP extension stopped the file upload")];
						break;
					}
				}
			break;
			default:
				echo json_encode(_("Error: You should never see this"), JSON_THROW_ON_ERROR);
			break;
		}
	}

	public function getLanguageLicense($lang) {
		$packages = $this->getPackages();
		if (empty($packages)) {
			return false;
		}

		foreach ($packages as $package) {
			if ($package['language'] == $lang) {
				$filename = $package['type'] . '-' . $package['module'] . '-' . $package['language'] . '-license.txt';
				try {
					$filedata = $this->getRemoteFile("/sounds/" . $filename);
					if (!empty($filedata)) {
						return $filedata;
					} else {
						return true;
					}
				} catch(\Exception) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get Language Names
	 * @return array Array of language keys to Names
	 */
	public function getLanguageNames() {
		$names = ['cs' => _('Czech'), 'de' => _('German'), 'en' => _('English'), 'es' => _('Spanish'), 'fa' => _('Persian'), 'fi' => _('Finish'), 'fr' => _('French'), 'he' => _('Hebrew'), 'it' => _('Italian'), 'ja' => _('Japanese'), 'nl' => _('Dutch'), 'no' => _('Norwegian'), 'pl' => _('Polish'), 'pt' => _('Portuguese'), 'ru' => _('Russian'), 'sv' => _('Swedish'), 'tr' => _('Turkish'), 'zh' => _('Chinese')];
		return $names;
	}

	/**
	 * Get language Locales
	 * @return array Array of Language locales
	 */
	public function getLocationNames() {
		$names = ['AU' => _('Australia'), 'BE' => _('Belgium'), 'BR' => _('Brazil'), 'CA' => _('Canada'), 'CH' => _('Switzerland'), 'CN' => _('China'), 'CO' => _('Colombia'), 'CZ' => _('Czech Republic'), 'DE' => _('Germany'), 'ES' => _('Spain'), 'FI' => _('Finland'), 'FR' => _('France'), 'GB' => _('United Kingdom'), 'HK' => _('Hong Kong'), 'IE' => _('Ireland'), 'IL' => _('Israel'), 'IN' => _('India'), 'IR' => _('Iran'), 'IT' => _('Italy'), 'JA' => _('Japan'), 'NL' => _('Netherlands'), 'NO' => _('Norway'), 'NZ' => _('New Zealand'), 'MX' => _('Mexico'), 'PL' => _('Poland'), 'PT' => _('Portugal'), 'SE' => _('Sweden'), 'TR' => _('Turkey'), 'TW' => _('Taiwan'), 'US' => _('United States'), 'ZA' => _('South Africa')];
		return $names;
	}

	public function getDefaultLocations() {
		$defaults = ['cs' => 'CZ', 'de' => 'DE', 'en' => 'US', 'es' => 'ES', 'fa' => 'IR', 'fi' => 'FI', 'fr' => 'FR', 'he' => 'IL', 'it' => 'IT', 'ja' => 'JA', 'nl' => 'NL', 'no' => 'NO', 'pl' => 'PL', 'pt' => 'PT', 'ru' => 'RU', 'sv' => 'SE', 'tr' => 'TR', 'zh' => 'CN'];
		return $defaults;
	}

	/**
	 * Get Languages
	 *
	 * This gets unique languges:
	 * OUT > Array
	 * (
	 * 	[en] => English
	 * 	[en_GB] => English (United Kingdom)
	 * )
	 * @return [type] [description]
	 */
	public function getAvailableLanguages() {
		$names 		  = $this->getLanguageNames();
		$locations 	  = $this->getLocationNames();
		$packagelangs = [];
		$packages 	  = $this->getPackages();

		if (!empty($packages)) {
			foreach ($packages as $package) {
				//Try to use locale_get_display_name if it's installed
				if(function_exists('locale_get_display_name')) {
					$language = $this->FreePBX->View->setLanguage();
					$name = locale_get_display_name($package['language'], $language);
				} else {
					$lang = explode('_', (string) $package['language'], 2);
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

		$languages = $packagelangs;
		if (empty($languages)) {
			$languages = [$this->default_lang => $names[$this->default_lang]];
		}
		asort($languages);
		return $languages;
	}

	public function getLanguages() {
		$installed = [];
		$languages = $this->getAvailableLanguages();
		$packages  = $this->getPackages();
		if (!empty($packages)) {
			foreach ($packages as $package) {
				if (!empty($package['installed'])) {
					$installed[$package['language']] = $languages[$package['language']];
				}
			}
		}

		$customs = $this->getCustomLanguages();
		if (!empty($customs)) {
			foreach ($customs as $customlang) {
				$installed[$customlang['language']] = $customlang['description'];
			}
		}

		return $installed;
	}

	/**
	 * Get the default global language
	 * @return string The language ID
	 */
	public function getLanguage() {
		$sql = sprintf("SELECT value FROM %s WHERE keyword = 'language'", $this->tables['settings']);
		$language = $this->db->getOne($sql);
		if (empty($language)) {
			$language = $this->default_lang;
		}
		return $language;
	}

	/**
	 * Set the default language
	 * @param string $language The Language ID
	 */
	public function setLanguage($language) {
		$sql = sprintf("UPDATE %s SET value = :language WHERE keyword = 'language'", $this->tables['settings']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':language' => $language]);
		needreload();
	}

	/**
	 * Get the format preference
	 * @return array List of format preferences
	 */
	public function getFormatPref() {
		$sql  	 = sprintf("SELECT value FROM %s WHERE keyword = 'formats'", $this->tables['settings']);
		$data	 = $this->db->getOne($sql);
		$formats = empty($data) ? $this->default_formats : explode(',', (string) $data);
		return $formats;
	}

	/**
	 * Set the format preferences
	 * @param array $formats List of format preferences
	 */
	public function setFormatPref($formats) {
		$sql = sprintf("REPLACE INTO %s (keyword, value) VALUES('formats', :formats)", $this->tables['settings']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':formats' => !empty($formats) ? implode(',', $formats) : 'g722,ulaw']);
	}

	/**
	 * Get all Custom Languages
	 * @return array Array of custom languages
	 */
	private function getCustomLanguages() {
		$customlangs = [];

		$sql = sprintf("SELECT id, language, description FROM %s", $this->tables['customlangs']);
		$sth = $this->db->prepare($sql);
		$sth->execute();
		$languages = $sth->fetchAll(\PDO::FETCH_ASSOC);

		if (!empty($languages)) {
			foreach ($languages as $language) {
				$customlangs[] = ['id' 		  => $language['id'], 'language' 	  => $language['language'], 'description' => $language['description']];
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
		$sql = sprintf("SELECT id, language, description FROM %s WHERE id = :id", $this->tables['customlangs']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':id' => $id]);
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
		$sql = sprintf("UPDATE %s SET language = :language, description = :description WHERE id = :id", $this->tables['customlangs']);
		$sth = $this->db->prepare($sql);
		$res = $sth->execute([':id' 		   => $id, ':language'    => $language, ':description' => $description]);

		$destdir = $this->getPathDestCustomLang($language);
		@mkdir($destdir);
	}

	/**
	 * Add a new custom language
	 * @param string $language    The language type
	 * @param string $description The language description
	 */
	private function addCustomLanguage($language, $description = '') {
		$sql = sprintf("INSERT INTO %s (language, description) VALUES (:language, :description)", $this->tables['customlangs']);
		$sth = $this->db->prepare($sql);
		$res = $sth->execute([':language'    => $language, ':description' => $description]);

		$destdir = $this->getPathDestCustomLang($language);
		@mkdir($destdir);
	}

	/**
	 * Delete custom language
	 * @param  int $id The language ID
	 */
	private function delCustomLanguage($id) {
		$language = $this->getCustomLanguage($id);
		if ($language['language'] == $this->getLanguage()) {
			/* Our current language was removed.  Fall back to default. */
			$this->setLanguage($this->default_lang);
		}

		$sql = sprintf("DELETE FROM %s WHERE id = :id", $this->tables['customlangs']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':id' => $id]);

		$destdir = $this->getPathDestCustomLang($language['language']);
		@rmdir($destdir);
	}

	private function getPathDestCustomLang($lang) {
		return sprintf("%s/%s/", $this->path_sounds, str_replace('/', '_', (string) $lang));
	}

	/**
	 * Get information about an installed package
	 * @param  array $package Array of information about package
	 * @return mixed          The installed version or null
	 */
	private function getPackageInstalled($package) {
		$sql = sprintf("SELECT * FROM %s WHERE id = :id", $this->tables['packages']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':id' => $package['id']]);
		$installed = $sth->fetch(\PDO::FETCH_ASSOC);

		return !empty($installed) ? $installed['installed'] : NULL;
	}

	/**
	 * Set package installed information
	 * @param array $package   Array of information about the package
	 * @param string $installed the new version to set
	 */
	private function setPackageInstalled($package, $installed) {
		$sql = sprintf("UPDATE %s SET installed = :installed WHERE id = :id", $this->tables['packages']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':installed' => $installed, ':id' 		 => $package['id']]);
	}

	/**
	 * Get list of all locally known packages
	 * @return array Array of package information(s)
	 */
	public function getPackages($installedOnly = false) {
		$sql = sprintf("SELECT * FROM %s", $this->tables['packages']);
		if($installedOnly){
			$sql .= " WHERE installed IS NOT NULL";
		}
		$sql .= " ORDER BY installed DESC, version DESC, language DESC";

		$sth = $this->db->prepare($sql);
		$sth->execute();

		$data = $sth->fetchAll(\PDO::FETCH_ASSOC);
		$packages = [];
		foreach ($data as $package) {
			$packages[$package['id']] = $package;
		}

		return $packages;
	}

	public function getPackageById($id) {
		$sql = sprintf("SELECT * FROM %s WHERE `id` = :id", $this->tables['packages']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':id' => $id]);

		$package = $sth->fetch(\PDO::FETCH_ASSOC);
		return $package;
	}

	/**
	 * Get online packages
	 * @return boolean True if the process finished successfully and False if an error occurred or no data was obtained.
	 */
	public function getOnlinePackages() {
		$version = getversion();
		// we need to know the freepbx major version we have running (ie: 12.0.1 is 12.0)
		preg_match('/(\d+\.\d+)/',(string) $version,$matches);
		$base_version = $matches[1];

		$packages = $this->getPackages();
		$xml = $this->getRemoteFile("/sounds-" . $base_version . ".xml");
		if(!empty($xml)) {
			libxml_use_internal_errors(true);
			$soundsobj = simplexml_load_string($xml);
			if($soundsobj === false){
				foreach(libxml_get_errors() as $error)
				{
					$this->logger->warning( sprintf(_("%s->%s - Soundlang Response: %s"), $this->module_name, __FUNCTION__, $error->message));
				}
				$soundsobj = json_encode([]);
			}
			/* Convert to an associative array */
			$sounds = json_decode(json_encode($soundsobj, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
			if (empty($sounds) || empty($sounds['sounds']) || empty($sounds['sounds']['package'])) {
				return false;
			}

			$available = $sounds['sounds']['package'];

			/* Delete packages that aren't installed */
			$sql = sprintf("DELETE FROM %s WHERE installed IS NULL", $this->tables['packages']);
			$sth = $this->db->prepare($sql);
			$sth->execute();

			/* Add / Update package versions */
			$sql = sprintf("REPLACE INTO %s (id, type, module, language, license, author, authorlink, format, version, installed) VALUES (:id, :type, :module, :language, :license, :author, :authorlink, :format, :version, :installed)", $this->tables['packages']);
			$sth = $this->db->prepare($sql);
			foreach ($available as $package) {
				$id = NULL;
				$package['installed'] = NULL;
				foreach ($packages as $k => $v) {
					if ($package['type'] == $v['type'] && $package['module'] == $v['module'] && $package['language'] == $v['language'] && $package['format'] == $v['format']) {
						/* Package already exists.  Use existing id/installed version. */
						$id = $k;
						$package['installed'] = $v['installed'];
					}
				}

				$res = $sth->execute([':id' 		  => $id, ':type' 	  => $package['type'], ':module' 	  => $package['module'], ':language'   => $package['language'], ':license' 	  => $package['license'] ?? '', ':author' 	  => $package['author'] ?? '', ':authorlink' => $package['authorlink'] ?? '', ':format' 	  => $package['format'], ':version' 	  => $package['version'], ':installed'  => $package['installed']]);
			}
			return true;
		} else {
			return false;
		}
	}

	public function installLanguage($language) {
		$packages = $this->getPackages();
		if (empty($packages)) {
			return false;
		}

		$status = true;
		foreach ($packages as $package)
		{
			if ($package['language'] == $language)
			{
				if (empty($package['installed']) || version_compare($package['version'], $package['installed'], 'gt'))
				{
					$formats = $this->getFormatPref();
					if (in_array($package['format'], $formats))
					{
						$result = $this->installPackage($package['id']);
						if ((is_array($result) && !$result['status']) || (is_bool($result) && !$result))
						{
							if (is_array($result) && !empty($result['message'])) 
							{
								$this->logger->warning( sprintf("%s->%s - %s", $this->module_name, __FUNCTION__, $result['message']));
							}
							$status = false;
						}
					}
				}
			}
		}
		return $status;
	}

	public function uninstallLanguage($language) {
		$packages = $this->getPackages();
		if (empty($packages)) {
			return false;
		}

		$status = true;
		foreach ($packages as $package) {
			if ($package['language'] == $language) {
				/* We don't check the format here.  Just delete everything. */
				if (! $this->uninstallPackage($package['id']))
				{
					$this->logger->warning( sprintf(_("%s->%s - Error uninstalling package %s (%s)"), $this->module_name, __FUNCTION__, $package['language'], $package['id']));
					$status = false;
				}
			}
		}
		return $status;
	}

	/**
	 * Install Package from online servers
	 * @param  array $package Array of information about the package
	 * @param bool $force Force redownload, even if it exists.
	 * @return mixed          return a string of the installed package or null
	 */
	public function installPackage($id, $force = false) {

		$package = $this->getPackageById($id);
		if (empty($package)) {
			return false;
		}

		$basename = $package['type'].'-'.$package['module'].'-'.$package['language'].'-'.$package['format'] .'-'.$package['version'];
		$soundsdir = $this->path_sounds;

		// Does this sound language package already exist on this machine?
		$txtfile = $soundsdir.'/'.$package['language'].'/'.$package['module'].'-'.$package['language'].'.txt';

		if ($force || !file_exists("$soundsdir/.$basename") || !file_exists($txtfile)) {
			// No. We need to fetch it.

			$tmpdir = "$soundsdir/tmp";
			if (!is_dir($tmpdir)) {
				mkdir($tmpdir);
			}

			// This is the file we want to download
			$filename = $basename . '.tar.gz';
			$filedata = $this->getRemoteFile("/sounds/" . $filename);
			file_put_contents($tmpdir . "/" . $filename, $filedata);

			// Extract it to the correct location
			$destdir = "$soundsdir/".$package['language']."/";
			@mkdir($destdir);

			$file_tar = sprintf("%s/%s", $tmpdir, $filename);
			try
			{
				$tar = new \PharData($file_tar);
				$tar->extractTo($destdir, null, true);
			}
			catch (\Exception $e)
			{
				$msg_exception = sprintf(_('Error: %s'), $e->getMessage());
				$this->logger->error( sprintf("%s->%s - %s", $this->module_name, __FUNCTION__, $msg_exception));
				return ['status' => false, 'message' => $msg_exception];
			}

			//https://issues.freepbx.org/browse/FREEPBX-14426
			$txtfilenoext = $soundsdir.'/'.$package['language'].'/'.$package['module'].'-'.$package['language'];
			if(!file_exists($txtfile) && file_exists($txtfilenoext)) {
				//missing .txt
				rename($txtfilenoext, $txtfile);
			}

			// If the txt file doesn't exist, there's something wrong with the package.
			if (!file_exists($txtfile)) {
				throw new \Exception(sprintf(_("Couldn't find %s - not in archive %s?"), $txtfile, $filename));
			}
			// Create our version file so we know it exists in the future.
			touch ("$soundsdir/.$basename");
		}

		// Get a list of sounds in this package.
		$prompts = file($txtfile, \FILE_SKIP_EMPTY_LINES);
		$files = [];
		foreach ($prompts as $prompt) {
			// If it's a comment, skip
			if ($prompt[0] == ";") {
				continue;
			}
			// Ignore the description
			$tmparr = explode(":", (string) $prompt);
			$files[] = $tmparr[0];
		}

		if (!$files) {
			throw new \Exception(sprintf(_("Unable to find any soundfiles in %s package"), $basename));
		}

		$row = [':type' 	=> $package['type'], ':module' 	=> $package['module'], ':language' => $package['language'], ':format' 	=> $package['format']];

		// Delete any prompts from this package previously
		$sql = sprintf("DELETE FROM `%s` WHERE `type`=:type AND `module`=:module AND `language`=:language AND `format`=:format", $this->tables['prompts']);
		$del = $this->db->prepare($sql);
		$del->execute($row);

		// Now load in the new files
		$sql = sprintf("INSERT INTO %s (type, module, language, format, filename) VALUES (:type, :module, :language, :format, :filename)", $this->tables['prompts']);
		$sth = $this->db->prepare($sql);
		foreach ($files as $file) {
			$row['filename'] = sprintf("%s.%s", $file, $package['format']);
			$res = $sth->execute($row);
		}

		$this->setPackageInstalled($package, $package['version']);
		return true;
	}

	public function recursivermdir($dir) {
		if (is_dir($dir)) {
			$objs = scandir($dir);
			foreach ($objs as $obj) {
				if ($obj != "." && $obj != "..") {
					$name = $dir . "/" . $obj;
					if (filetype($name) == "dir") {
						$this->recursivermdir($name);
					} else {
						unlink($name);
					}
				}
				reset($objs);
			}
			rmdir($dir);
		}
	}

	/**
	 * Uninstall a package
	 * @param  int 		$id ID of the package to uninstall
	 * @return boolean 	True if the package is uninstalled, False if the package not uninstalled.
	 */
	public function uninstallPackage($id)
	{
		$package = $this->getPackageById($id);
		if (empty($package)) {
			return false;
		}

		$soundsdir = $this->path_sounds;
		$tmpname = $package['type'].'-'.$package['module'].'-'.$package['language'].'-'.$package['format'] .'-';

		// Figure out which one we have, if any
		$installed = glob("$soundsdir/.$tmpname*");
		if ($installed) {
			foreach ($installed as $file) {
				unlink($file);
			}
		}

		$this->setPackageInstalled($package, NULL);

		$sql = sprintf("SELECT * FROM %s WHERE type = :type AND module = :module AND language = :language AND format = :format", $this->tables['prompts']);
		$sth = $this->db->prepare($sql);
		$sth->execute([':type' 	=> $package['type'], ':module' 	=> $package['module'], ':language' => $package['language'], ':format' 	=> $package['format']]);
		$files = $sth->fetchAll(\PDO::FETCH_ASSOC);

		if ($files) {
			$destdir = "$soundsdir/" . $package['language'] . "/";

			// Delete the soundfiles from this pack
			foreach ($files as $file) {
				@unlink($destdir . $file['filename']);
			}

			/* Purge installed prompts */
			$sql = sprintf("DELETE FROM %s WHERE type = :type AND module = :module AND language = :language AND format = :format", $this->tables['prompts']);
			$sth = $this->db->prepare($sql);
			$sth->execute([':type' 	=> $package['type'], ':module' 	=> $package['module'], ':language' => $package['language'], ':format' 	=> $package['format']]);

			needreload();
		}
		return true;
	}

	/**
	 * Retrieve a remote file
	 * Stores file into memory
	 * @param  string $path The full path to said file
	 * @return string       binary representation of file
	 */
	private function getRemoteFile($path) {
		$modulef = \module_functions::create();

		$contents = null;

		$mirrors = $modulef->generate_remote_urls($path, true);

		$params = $mirrors['options'];
		$params['sv'] = 2; // Stats version
		$params['soundlangver'] = 2;
		$params['canredirect'] = 1;

		$exceptions = [];
		foreach($mirrors['mirrors'] as $url) {
			set_time_limit($this->maxTimeLimit);

			$pest = $this->FreePBX->Curl->pest($url);
			$pest->curl_opts[\CURLOPT_TIMEOUT] = $this->maxTimeLimit;
			try {
				$contents = $pest->post($url . $path, $params);
				// If we were redirected to a different CDN, use that instead.
				if (!$contents && !empty($pest->last_response['meta']['redirect_url'])) {
					$newurl = $pest->last_response['meta']['redirect_url'];
					$urlarr = parse_url((string) $newurl);

					$host = $urlarr['scheme']."://".$urlarr['host'];
					if (!empty($urlarr['port'])) {
						$host .= ":".$urlarr['port'];
					}

					$path = $urlarr['path'];

					$pest = $this->FreePBX->Curl->pest($host);
					$pest->curl_opts[\CURLOPT_TIMEOUT] = $this->maxTimeLimit;
					$contents = $pest->get($path);
				}
			} catch(\Exception $e) {
				$exceptions[] = $e;
			}
			if (!empty($contents)) {
				return $contents;
			}
		}
		if(!empty($exceptions)) {
			$message = '';
			$code = '';
			foreach($exceptions as $e) {
				if($this->config->get('MODULE_REPO') !== $this->config->get_conf_default_setting('MODULE_REPO')) {
					$this->config->reset_conf_settings(['MODULE_REPO'],true);
					$code = 500;
					$message = _("The mirror server did not return the correct response and had been previously changed from the default server(s), it has now been reset back to the standard default. Please try again");
				} else {
					$code = $e->getCode();
					$msg = $e->getMessage();
					$message .= !empty($msg) ? $msg : sprintf(_("Error %s returned from remote servers %s"),$code,json_encode($mirrors['mirrors'], JSON_THROW_ON_ERROR));
					$message .= ", ";
				}

			}
			$message = rtrim(trim($message),",");

			throw new \Exception($message,$code);
		} else {
			throw new \Exception(sprintf(_("Unknown Error. Response was empty from %s"),json_encode($mirrors['mirrors'], JSON_THROW_ON_ERROR)),0);
		}
	}
}

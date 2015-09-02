<?php
//Namespace should be FreePBX\Console\Command
namespace FreePBX\Console\Command;

//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Tables
use Symfony\Component\Console\Helper\TableHelper;
//Process
use Symfony\Component\Process\Process;
class Soundlang extends Command {
  protected function configure(){
    $this->setName('sounds')
      ->setDescription(_('Sound Language Prompts'))
      ->setDefinition(array(
        new InputArgument('args', InputArgument::IS_ARRAY, null, null),));
  }
  protected function execute(InputInterface $input, OutputInterface $output){
    $args = $input->getArgument('args');
    $command = isset($args[0])?$args[0]:'';
		$soundlang = \FreePBX::create()->Soundlang;
		switch ($command) {
			case "list":
				$soundlang->getOnlinePackages();
				$list = $soundlang->getPackages();
				$rows = array();
				$names = $soundlang->getLanguageNames();
				$localenames = $soundlang->getLocationNames();
				foreach ($list as $key => $value) {
					$parts = explode('_',$value['language']);
					$lang = $names[$parts[0]] . (isset($parts[1]) ? ' - '.$localenames[$parts[1]] : '');
					$rows[] = array($key, $value['module'],$lang,$value['format'],$value['version'],$value['installed']);
				}
				$table = $this->getHelper('table');
				$table->setHeaders(array("ID", "Module","Language","Format","Available","Installed"));
				$table->setRows($rows);
				$table->render($output);
	    break;
			case "install":
				$list = $soundlang->getPackages();
				if(!isset($args[1])) {
					$output->writeln("<error>"._("The command provided is not valid")."</error>");
					exit(4);
				}
				$id = $args[1];
				if(!isset($list[$id])) {
					$output->writeln("<error>"._("That is not a valid ID")."</error>");
					exit(4);
				}
				$soundlang->installPackage($list[$id]);
				$output->writeln(sprintf(_("Successfully installed %s"),$list[$id]['module']."-".$list[$id]['language']."-".$list[$id]['format']."-".$list[$id]['version']));
			break;
			case "uninstall":
				$list = $soundlang->getPackages();
				if(!isset($args[1])) {
					$output->writeln("<error>"._("The command provided is not valid")."</error>");
					exit(4);
				}
				$id = $args[1];
				if(!isset($list[$id])) {
					$output->writeln("<error>"._("That is not a valid ID")."</error>");
					exit(4);
				}
				$soundlang->uninstallPackage($list[$id]);
				$output->writeln(sprintf(_("Successfully uninstalled %s"),$list[$id]['module']."-".$list[$id]['language']."-".$list[$id]['format']."-".$list[$id]['version']));
			break;
			case "global":
				if(!isset($args[1])) {
					$lang = $soundlang->getLanguage();
					$output->writeln($lang);
				} else {
					switch($args[1]) {
						case "list":
							$list = $soundlang->getLanguages();
							$default = $soundlang->getLanguage();
							foreach ($list as $key => $value) {
								$def = ($key == $default) ? "X" : "";
								$rows[] = array($key, $value, $def);
							}
							$table = $this->getHelper('table');
							$table->setHeaders(array("ID", "Name", "Default"));
							$table->setRows($rows);
							$table->render($output);
						break;
						default:
							$list = $soundlang->getLanguages();
							if(!isset($list[$args[1]])) {
								$output->writeln("<error>"._("That is not a valid ID")."</error>");
								exit(4);
							}
							$soundlang->setLanguage($args[1]);
							$output->writeln(sprintf(_("Successfully set default language to %s, you will need to reload"),$args[1]));
							needreload();
						break;
					}
				}
			break;
	    default:
	      $output->writeln("<error>The command provided is not valid.</error>");
        $output->writeln("Avalible commands are:");
        $output->writeln("<info>list</info> - List all language packages");
        $output->writeln("<info>install <id></info> - Install language pack by ID");
        $output->writeln("<info>uninstall <id></info> - Uninstall language pack by ID");
        $output->writeln("<info>global list</info> - List the Avalible global languages");
        $output->writeln("<info>global <id></info> - Set the global language by ID");
	      exit(4);
	    break;
    }
  }
}

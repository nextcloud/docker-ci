<?php
/**
 * @copyright Copyright (c) 2017 Jakob Sack <nextcloud@jakobsack.de>
 *
 * @author Jakob Sack <nextcloud@jakobsack.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
require __DIR__ . '/vendor/autoload.php';

class TranslatableApp {
	private $appPath;
	private $name;
	private $translatableFiles; 
	private $dummyFileName;
	private $ignoreFiles;
	private $translationsPath;

	 public function __construct($appPath, $translationsPath) {
		$this->appPath = $appPath;
		$this->translationsPath = $translationsPath;

		$this->translatableFiles = [];
		$this->ignoreFiles = [];
		$this->dummyFileName = $this->appPath . '/specialAppInfoFakeDummyForL10nScript.php';

		$this->setAppName();

		switch ($this->name) {
			case 'bruteforcesettings':
				$this->ignoreFiles[] = 'js/bruteforcesettings.js';
				break;
			case 'oauth2':
				$this->ignoreFiles[] = 'js/oauth2.js';
				break;
			case 'settings':
				$this->ignoreFiles[] = 'js/settings-vue.js';
				break;
			case 'updatenotification':
				$this->ignoreFiles[] = 'js/merged.js';
				break;
		}
	}

	 public function createPotFile() {
		$pathToPotFile = $this->translationsPath . '/templates/' . $this->name . '.pot';

		// Gather required data
		$this->readIgnoreList();
		$this->createFakeFileForAppInfo();
		$this->findTranslatableFiles();

		// Let gettext create the pot file
		$additionalArguments = ' --add-comments=TRANSLATORS --from-code=UTF-8 --package-version="3.14159" --package-name="Nextcloud" --msgid-bugs-address="translations\@example.com"';
		foreach ($this->translatableFiles as $entry) {
			$output = '--output=' . escapeshellarg($pathToPotFile);

			$keywords = '';
			if (substr($entry, -4) === '.php') {
				$keywords = '--keyword=t --keyword=n:1,2';
			} else {
				$keywords = '--keyword=t:2 --keyword=n:2,3';
			}

			$language = '--language=';
			if (substr($entry, -4) === '.php') {
				$language .= 'PHP';
			} else {
				$language .= 'Javascript';
			}

			$joinexisting = '';
			if (file_exists($pathToPotFile)) {
				$joinexisting = '--join-existing';
			}

			exec('xgettext ' . $output . ' ' . $joinexisting . ' ' . $keywords . ' ' . $language . ' ' . escapeshellarg($entry) . ' ' . $additionalArguments);
		}
		
		// Don't forget to remove the temporary file
		$this->deleteFakeFileForAppInfo();
	}

	public function createNextcloudFiles() {
		foreach ($this->findLanguages() as $language) {
			$poFile = $this->translationsPath . '/' . $language . '/' . $this->name . '.po';
			$translations = Gettext\Translations::fromPoFile($poFile);

			$strings = [];
			$plurals = $translations->getHeader('Plural-Forms');

			foreach ($translations as $translation) {
				if (!$translation->hasTranslation()) {
					// Skip if we have no translation (for the base form)
					continue;
				}

				if ($translation->hasPlural()) {
					$identifier = $this->escape('_' . $translation->getOriginal() . '_::_' . $translation->getPlural() . '_');

					$variants = [$this->escape($translation->getTranslation())];
					foreach ($translation->getPluralTranslations() as $plural) {
						if ($plural === '') {
							// We skip the complete string if not all plural forms exist
							continue 2;
						}
						$variants[] = $this->escape($plural);
					}

					$strings[] = $identifier . ' : [' . join(',', $variants) . ']';
				} else {
					$strings[] = $this->escape($translation->getOriginal()) . ' : ' . $this->escape($translation->getTranslation());
				}
			}

			// Nothing translated :-( skip this file
			if (count($strings) === 0) {
				continue;
			}

			$this->writeJsFile($language, $plurals, $strings);
			$this->writeJsonFile($language, $plurals, $strings);
		}
	}

	private function writeJsFile($language, $plurals, $strings) {
		$outfile = fopen($this->appPath . '/l10n/' . $language . '.js', 'w');
		fwrite($outfile, 'OC.L10N.register(' . PHP_EOL);
		fwrite($outfile, '    "' . $this->name . '",' . PHP_EOL);
		fwrite($outfile, '    {' . PHP_EOL);
		fwrite($outfile, '    ');
		fwrite($outfile, join(',' . PHP_EOL . '    ', $strings));
		fwrite($outfile, PHP_EOL . '},' . PHP_EOL);
		fwrite($outfile, $this->escape($plurals) . ');' . PHP_EOL);
		fclose($outfile);
	}

	private function writeJsonFile($language, $plurals, $strings){
		$outfile = fopen($this->appPath . '/l10n/' . $language . '.json', 'w');
		fwrite($outfile, '{ "translations": {' . PHP_EOL);
		fwrite($outfile, '    ');
		fwrite($outfile, join(',' . PHP_EOL . '    ', $strings));
		fwrite($outfile, PHP_EOL . '},"pluralForm" :' . $this->escape($plurals) . PHP_EOL . '}');
		fclose($outfile);
	}

	private function escape($string) {
		return Gettext\Generators\Po::convertString($string);
	}

	private function findTranslatableFiles($path='') {
		$realPath = $path === '' ? $this->appPath : $this->appPath . '/' . $path;

		$directoryectoryContent = scandir($realPath);
		foreach ($directoryectoryContent as $entry) {
			if ($entry[0] === '.') {
				continue;
			}

			$newPath = $path === '' ? $entry : $path . '/' . $entry;
			$newRealPath = $this->appPath . '/' . $newPath;

			if (in_array($newPath, $this->ignoreFiles)) {
				continue;
			}
			if (is_dir($newRealPath) && $entry != 'l10n' && $entry != 'node_modules') {
				$this->findTranslatableFiles($newPath);
			}
			if (is_file($newRealPath)) {
				if (substr($entry, -4) === '.php' ||
					(substr($entry, -3) === '.js' && substr($entry, -7) !== '.min.js') ||
					substr($entry, -4) === '.vue' ||
					substr($entry, -4) === '.jsx' ||
					substr($entry, -5) === '.html' ||
					substr($entry, -3) === '.ts' ||
					substr($entry, -4) === '.tsx') {
					$this->translatableFiles[] = $newRealPath;
				}
			}
		}
	}

	private function readIgnoreList() {
		$ignoreFile = $this->appPath . '/l10n/ignorelist';
		if (!is_file($ignoreFile)) {
			return [];
		}

		return file($ignoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}

	private function findLanguages() {
		$languages = [];
		$directoryectoryContent = scandir($this->translationsPath);
		foreach ($directoryectoryContent as $entry) {
			if ($entry[0] === '.' || $entry === 'templates' || !is_dir($this->translationsPath . '/' . $entry)) {
				continue;
			}

			if (is_file($this->translationsPath . '/' . $entry . '/' . $this->name . '.po')) {
				$languages[] = $entry;
			}
		}

		return $languages;
	}

	private function createFakeFileForAppInfo() {
		$entryName = $this->appPath . '/appinfo/info.xml';
		
		if (!file_exists($entryName)) {
			return false;
		}
		
		$strings = [];
		$xml = simplexml_load_file($entryName);
		
		if ($xml->name) {
			$strings[] = $xml->name->__toString();
		}

		if ($xml->navigations) {
			foreach ($xml->navigations as $navigation) {
				$name = $navigation->navigation->name->__toString();
				if (!in_array($name, $strings)) {
					$strings[] = $name;
				}
			}
		}

		if ($xml->name) {
			if ($xml->name->count() > 1) {
				foreach ($xml->name as $name) {
					if ((string)$name->attributes()->lang === 'en') {
						$name = $name->__toString();
						if (!in_array($name, $strings)) {
							$strings[] = $name;
						}
					}
				}
			} else {
				$name = $xml->name->__toString();
				if (!in_array($name, $strings)) {
					$strings[] = $name;
				}
			}
		}

		if ($xml->summary) {
			if ($xml->summary->count() > 1) {
				foreach ($xml->summary as $summary) {
					if ((string)$summary->attributes()->lang === 'en') {
						$name = $summary->__toString();
						if (!in_array($name, $strings)) {
							$strings[] = $name;
						}
					}
				}
			} else {
				$name = $xml->summary->__toString();
				if (!in_array($name, $strings)) {
					$strings[] = $name;
				}
			}
		}

		if ($xml->description) {
			if ($xml->description->count() > 1) {
				foreach ($xml->description as $description) {
					if ((string)$description->attributes()->lang === 'en') {
						$name = $description->__toString();
						if (!in_array($name, $strings)) {
							$strings[] = trim($name);
						}
					}
				}
			} else {
				$name = $xml->description->__toString();
				if (!in_array($name, $strings)) {
					$strings[] = trim($name);
				}
			}
		}
		
		$content = '<?php' . PHP_EOL;
		foreach ($strings as $string) {
			$content .= '$l->t(' . $this->escape($string) . ');' . PHP_EOL;
		}
		
		file_put_contents($this->dummyFileName, $content);		
	}

	private function deleteFakeFileForAppInfo() {
		if (is_file($this->dummyFileName)){
			unlink($this->dummyFileName);
		}
	}

	private function setAppName() {
		$xmlFile = $this->appPath . '/appinfo/info.xml';
		
		$this->name = basename($this->appPath);

		if (!file_exists($xmlFile)) {
			return;
		}
		
		$xml = simplexml_load_file($xmlFile);
		
		if ($xml->name) {
			$this->name = $xml->id->__toString();
		}
	}
}

class TranslationTool {
	private $translationPath;
	private $appPaths;

	public function __construct(){
		$this->translationPath = getcwd() . '/translationfiles';
		$this->appPaths = [];
		
		if (!is_dir($this->translationPath)) {
			mkdir($this->translationPath);
		}
	}

	public function checkEnvironment() {
		// Check if the version of xgettext is at least 0.18.3
		$output = [];
		exec('xgettext --version', $output);

		// we assume the first line looks like this 'xgettext (GNU gettext-tools) 0.19.3'
		$version = trim(substr($output[0], 29));

		if (version_compare($version, '0.18.3', '<')) {
			echo 'Minimum expected version of xgettext is 0.18.3. Detected: ' . $version . '".' . PHP_EOL;
			return false;
		}

		$this->findApps(getcwd());
		if (count($this->appPaths) === 0) {
			echo 'Could not find translatable apps. Make sure that the folder "l10n" is present.' . PHP_EOL;
			return false;
		}

		return true;
	}

	public function createPotFiles() {
		// Recreate folder for the templates
		$this->rrmdir($this->translationPath . '/templates');
		mkdir($this->translationPath . '/templates');

		// iterate over all apps
		foreach ($this->appPaths as $appPath) {
			$app = new TranslatableApp($appPath, $this->translationPath);
			$app->createPotFile();
		}
	}

	public function convertPoFiles() {
		foreach ($this->appPaths as $appPath) {
			$app = new TranslatableApp($appPath, $this->translationPath);
			$app->createNextcloudFiles();
		}
	}

	private function findApps($path){
		$directoryectoryContent = scandir($path);
		foreach ($directoryectoryContent as $entry) {
			if ($entry[0] === '.') {
				continue;
			}

			$newPath = $path . '/' . $entry;
			if (!is_dir($newPath)) {
				continue;
			}

			if ($entry === 'l10n') {
				$this->appPaths[] = $path;
			} else {
				$this->findApps($newPath);
			}
		}
	}


	private function rrmdir($path) {
		if (!is_dir($path)) {
			return;
		}

		$directory = opendir($path);
		while (false !== ($entry = readdir($directory))) {
			if (($entry !== '.') && ($entry !== '..')) {
				$fullPath = $path . '/' . $entry;

				if (is_dir($fullPath)) {
					$this->rrmdir($fullPath);
				} else {
					unlink($fullPath);
				}
			}
		}
		closedir($directory);

		rmdir($path);
	}
}

// read the command line arguments
if(count($argv) < 2) {
	echo 'Missing arguments' . PHP_EOL;
	echo 'call "' . $argv[0] . ' $task [$appName]' . PHP_EOL;
	echo '$task: create-pot-files || convert-po-files' . PHP_EOL;
	return false;
}
$task = $argv[1];

$tool = new TranslationTool();
if (!$tool->checkEnvironment()) {
	return false;
}

if ($task === 'create-pot-files') {
	$tool->createPotFiles();
} elseif ($task === 'convert-po-files') {
	$tool->convertPoFiles();
} else {
	echo 'Unknown task: "' . $task . '".' . PHP_EOL;
	return false;
}

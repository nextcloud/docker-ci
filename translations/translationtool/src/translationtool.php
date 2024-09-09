<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 Jakob Sack <nextcloud@jakobsack.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require __DIR__ . '/vendor/autoload.php';

class TranslatableApp {
	private string $appPath;
	private string $name = '';
	private string $fakeAppInfoFile;
	private string $fakeVueFile;
	private string $fakeLocaleFile;
	private array $ignoreFiles = [];
	private string $translationsPath;
	private TranslationTool $tool;

	public function __construct(string $appPath, string $translationsPath, TranslationTool $tool) {
		$this->appPath = $appPath;
		$this->translationsPath = $translationsPath;
		$this->tool = $tool;

		$this->fakeAppInfoFile = $this->appPath . '/specialAppInfoFakeDummyForL10nScript.php';
		$this->fakeVueFile = $this->appPath . '/specialVueFakeDummyForL10nScript.js';
		$this->fakeLocaleFile = $this->appPath . '/specialLocaleFakeDummyForL10nScript.php';

		$this->setAppName();

		if (file_exists($this->appPath . '/.l10nignore')) {
			$lines = explode("\n", file_get_contents($this->appPath . '/.l10nignore'));
			foreach ($lines as $line) {
				if (!$line) {
					continue;
				}
				if (substr($line, 0, 1) === '#') {
					continue;
				}
				$this->ignoreFiles[] = $line;
			}
		}

		echo "Those files are ignored:\n";
		print_r($this->ignoreFiles);
	}

	public function createOrCheckPotFile(bool $checkFiles = false): void {
		$pathToPotFile = $this->translationsPath . '/templates/' . $this->name . '.pot';

		// Gather required data
		$this->createFakeFileForAppInfo();
		$this->createFakeFileForVueFiles();
		$this->createFakeFileForLocale();
		$translatableFiles = $this->findTranslatableFiles(
			['.php', '.js', '.jsx', '.mjs', '.html', '.ts', '.tsx'],
			['.min.js']
		);

		// Let gettext create the pot file
		$additionalArguments = ' --add-comments=TRANSLATORS --from-code=UTF-8 --package-version="3.14159" --package-name="Nextcloud" --msgid-bugs-address="translations\@example.com"';
		foreach ($translatableFiles as $entry) {
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

			$extractAll = $tmpfname = $skipErrors = '';
			if ($checkFiles) {
				$extractAll = '--extract-all';

				// modify output
				$tmpfname = tempnam(sys_get_temp_dir(), 'checkpot');
				$output = '--output=' . $tmpfname;
				// extract-all generates a recurrent warning
				$skipErrors = '2>/dev/null';
			}

			$xgetCmd = 'xgettext ' . $output . ' ' . $joinexisting . ' ' . $keywords . ' ' . $language . ' ' . escapeshellarg($entry) . ' ' . $additionalArguments . ' ' . $extractAll . ' ' . $skipErrors;
			$this->tool->log($xgetCmd);
			exec($xgetCmd);

			// checking files
			if ($checkFiles) {
				$this->checkMissingTranslations($entry, $tmpfname);
				unlink($tmpfname);
			}
		}

		// Don't forget to remove the temporary file
		$this->deleteFakeFileForAppInfo();
		$this->deleteFakeFileForVueFiles();
		$this->deleteFakeFileForLocale();
	}

	private function checkMissingTranslations(string $entry, string $tmpfname): void {
		$translations = Gettext\Translations::fromPoFile($tmpfname);
		$first = true;
		foreach($translations as $translation) {
			if (preg_match_all('/(^|[^a-zA-Z_]+)(t\([^\)]*\))/', $translation->getOriginal(), $matches)) {
				$suspects = [];
				foreach($matches[2] as $miss) {
					if (preg_match('/["\']'.$this->name.'["\']/', $miss)) {
						$suspects[] = $miss;
					}
				}
				if (empty($suspects)) {
					continue;
				}
				if ($first) {
					echo '** Warning: Check potentially missing translations sentences: ' . $this->name . ' ' . $entry . PHP_EOL;
					$first = false;
				}
				if ($translation->hasReferences()) {
					echo '> Starting at line ' . $translation->getReferences()[0][1] . PHP_EOL;
				}
				foreach($suspects as $suspect) {
					echo '  -> ' . $suspect . PHP_EOL;
				}
			}
		}
	}

	public function createNextcloudFiles(): void {
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

	private function writeJsFile(string $language, string $plurals, array $strings): void {
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

	private function writeJsonFile(string $language, string $plurals, array $strings): void {
		$outfile = fopen($this->appPath . '/l10n/' . $language . '.json', 'w');
		fwrite($outfile, '{ "translations": {' . PHP_EOL);
		fwrite($outfile, '    ');
		fwrite($outfile, join(',' . PHP_EOL . '    ', $strings));
		fwrite($outfile, PHP_EOL . '},"pluralForm" :' . $this->escape($plurals) . PHP_EOL . '}');
		fclose($outfile);
	}

	private function escape(string $string): string {
		return Gettext\Generators\Po::convertString($string);
	}

	private function hasExtension(string $fileName, array $extensions): bool {
		foreach ($extensions as $ext) {
			if (substr($fileName, -strlen($ext)) === $ext) {
				return true;
			}
		}
		return false;
	}

	private function findTranslatableFiles(array $extensions, array $ignoredExtensions = [], string $path = ''): array {
		$realPath = $path === '' ? $this->appPath : $this->appPath . '/' . $path;
		$translatable = [];

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
			foreach ($this->ignoreFiles as $ignoredFile) {
				if (strpos($newPath, $ignoredFile) === 0) {
					continue 2;
				}
			}
			if (is_dir($newRealPath) && $entry !== 'l10n' && $entry !== 'node_modules') {
				$translatable = array_merge($translatable, $this->findTranslatableFiles($extensions, $ignoredExtensions, $newPath));
			}
			if (is_file($newRealPath)) {
				if ($this->hasExtension($entry, $extensions)
					&& !$this->hasExtension($entry, $ignoredExtensions)) {
					$translatable[] = $newRealPath;
				}
			}
		}

		return $translatable;
	}

	private function findLanguages(): array {
		$languages = [];
		$directoryContent = scandir($this->translationsPath);
		foreach ($directoryContent as $entry) {
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
		$xml = simplexml_load_string(file_get_contents($entryName));

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

		file_put_contents($this->fakeAppInfoFile, $content);
	}

	private function createFakeFileForVueFiles(): void {
		$fakeFileContent = '';

		foreach ($this->findTranslatableFiles(['.vue']) as $vueFile) {
			$vueSource = file_get_contents($vueFile);
			if ($vueSource === false) {
				echo 'Warning: could not read ' . $vueFile . PHP_EOL;
				continue;
			}

			// t
			preg_match_all("/\Wt\s*\(\s*'?([\w.]+)'?,\s*'(.+)'/", $vueSource, $singleQuoteMatches);
			preg_match_all("/\Wt\s*\(\s*\"?([\w.]+)\"?,\s*\"(.+)\"/", $vueSource, $doubleQuoteMatches);
			preg_match_all("/\Wt\s*\(\s*\'?([\w.]+)\'?\s*,\s*\`(.+)\`\s*\)/msU", $vueSource, $templateQuoteMatches);
			$matches0 = array_merge($singleQuoteMatches[0], $doubleQuoteMatches[0], $templateQuoteMatches[0]);
			$matches2 = array_merge($singleQuoteMatches[2], $doubleQuoteMatches[2], $templateQuoteMatches[2]);
			foreach (array_keys($matches2) as $k) {
				$match = $matches2[$k];
				$fakeFileContent .= $this->getTranslatorHintWithVueSource($vueFile, $vueSource, $matches0[$k]);
				$fakeFileContent .= "t('" . $this->name . "', '" . preg_replace('/\s+/', ' ', $match) . "');" . PHP_EOL;
			}

			// n
			preg_match_all("/\Wn\s*\(\s*'?([\w.]+)'?,\s*'(.+)'\s*,\s*'(.+)'\s*(.+)/", $vueSource, $singleQuoteMatches);
			preg_match_all("/\Wn\s*\(\s*\"?([\w.]+)\"?,\s*\"(.+)\"\s*,\s*\"(.+)\"\s*(.+)/", $vueSource, $doubleQuoteMatches);
			preg_match_all("/\Wn\s*\(\s*\'?([\w.]+)\'?\s*,\s*\`(.+)\`\s*,\s*\`(.+)\`\s*\)/msU", $vueSource, $templateQuoteMatches);
			$matches0 = array_merge($singleQuoteMatches[0], $doubleQuoteMatches[0], $templateQuoteMatches[0]);
			$matches2 = array_merge($singleQuoteMatches[2], $doubleQuoteMatches[2], $templateQuoteMatches[2]);
			$matches3 = array_merge($singleQuoteMatches[3], $doubleQuoteMatches[3], $templateQuoteMatches[3]);
			foreach (array_keys($matches2) as $k) {
				$match2 = $matches2[$k];
				$match3 = $matches3[$k];
				$fakeFileContent .= $this->getTranslatorHintWithVueSource($vueFile, $vueSource, $matches0[$k]);
				$fakeFileContent .= "n('" . $this->name . "', '" . preg_replace('/\s+/', ' ', $match2) . "', '" . preg_replace('/\s+/', ' ', $match3) . "');" . PHP_EOL;
			}
		}

		file_put_contents($this->fakeVueFile, $fakeFileContent);
	}

	private function getTranslatorHintWithVueSource(string $vueFile, string $content, string $translation): string {
		$relativeVuePath = substr($vueFile, strpos($vueFile, $this->name . '/src/') + strlen($this->name . '/'));

		$position = strpos($content, $translation);
		$contentBefore = substr($content, 0, $position);
		$contentAfter = substr($content, $position);
		$lineNumber = substr_count($contentBefore, "\n") + 1;

		$linesBefore = explode("\n", $contentBefore);
		$linesAfter = explode("\n", $contentAfter);
		$previousLine = $linesBefore[$lineNumber - 2];
		$currentLine = $linesAfter[0];

		// If we have a translation hint in the current line, we use it
		// This prevents mismatching hints if we have two translations
		// over consecutive lines
		// Like https://github.com/nextcloud/forms/blob/5c905b36b1ce3ca1848175d39d813581732e159d/src/views/Results.vue#L261-L263
		$searchComment = strpos($currentLine, 'TRANSLATORS') !== false
			? $currentLine
			: $previousLine;

		// We try to find a comment with the translators hint
		// <!-- TRANSLATORS: This is a comment -->
		// <!-- TRANSLATORS : This is a comment -->
		// <!-- TRANSLATORS This is a comment -->
		// t('forms', 'Save to home') // TRANSLATORS: Export the file to the home path
		$re = '/TRANSLATORS[: ]*(.*)/m';
		preg_match_all($re, $searchComment, $matches, PREG_SET_ORDER, 0);

		// If we have a comment, we use it
		if (count($matches) > 0 && count($matches[0]) > 1) {
			// Remove double spaces
			$comment = str_replace('  ', ' ', $matches[0][1]);
			// Remove leading and trailing spaces
			$comment = trim($comment);
			// Remove newlines
			$comment = str_replace("\n", '', $comment);
			// Remove html end comment
			$comment = str_replace('-->', '', $comment);
			return str_replace('  ', ' ', '// TRANSLATORS ' . $comment . " ($relativeVuePath:$lineNumber)" . PHP_EOL);
		}

		return '// TRANSLATORS ' . $relativeVuePath . ':' . $lineNumber . PHP_EOL;
	}

	private function deleteFakeFileForAppInfo(): void {
		if (is_file($this->fakeAppInfoFile)) {
			unlink($this->fakeAppInfoFile);
		}
	}

	private function deleteFakeFileForVueFiles(): void {
		if (is_file($this->fakeVueFile)) {
			unlink($this->fakeVueFile);
		}
	}

	private function setAppName(): void {
		$xmlFile = $this->appPath . '/appinfo/info.xml';

		$this->name = basename($this->appPath);

		if (!file_exists($xmlFile)) {
			return;
		}

		$xml = simplexml_load_string(file_get_contents($xmlFile));

		if ($xml->name) {
			$this->name = $xml->id->__toString();
		}
	}

	private function createFakeFileForLocale() {
		if ($this->name !== 'settings') {
			return false;
		}
		$entryName = $this->appPath . '/../resources/locales.json';

		if (!file_exists($entryName)) {
			return false;
		}

		$strings = [];
		$locales = json_decode(file_get_contents($entryName), true);

		foreach ($locales as $locale) {
			$strings[] = $locale['name'];
		}

		$content = '<?php' . PHP_EOL;
		foreach ($strings as $string) {
			$content .= '$l->t(' . $this->escape($string) . ');' . PHP_EOL;
		}

		file_put_contents($this->fakeLocaleFile, $content);
	}

	private function deleteFakeFileForLocale(): void {
		if (is_file($this->fakeLocaleFile)) {
			unlink($this->fakeLocaleFile);
		}
	}
}

class TranslationTool {
	private string $translationPath;
	private array $appPaths;
	private int $verbose = 0;

	public function __construct() {
		$this->translationPath = getcwd() . '/translationfiles';
		$this->appPaths = [];

		if (!is_dir($this->translationPath)) {
			mkdir($this->translationPath);
		}
	}

	public function setVerbose(int $verbose): void {
		$this->verbose = $verbose;
	}

	public function checkEnvironment(): bool {
		// Check if the version of xgettext is at least 0.18.3
		$output = [];
		exec('xgettext --version', $output);

		// we assume the first line looks like this 'xgettext (GNU gettext-tools) 0.19.3'
		$version = trim(substr($output[0], 29));

		$this->log('xgettext version: '. $version);

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

	public function createPotFiles(): void {
		// Recreate folder for the templates
		$this->rrmdir($this->translationPath . '/templates');
		mkdir($this->translationPath . '/templates');

		// iterate over all apps
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$app->createOrCheckPotFile();
		}
	}

	public function convertPoFiles(): void {
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$app->createNextcloudFiles();
		}
	}

	public function checkFiles(): void {
		// iterate over all apps
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$app->createOrCheckPotFile(true);
		}
	}

	private function findApps(string $path): void {
		$directoryectoryContent = scandir($path);
		foreach ($directoryectoryContent as $entry) {
			if ($entry[0] === '.') {
				continue;
			}

			$newPath = $path . '/' . $entry;
			if (!is_dir($newPath)) {
				continue;
			}

			if ($entry === 'node_modules') {
				continue;
			}

			if ($entry === 'l10n') {
				$this->appPaths[] = $path;
			} else {
				$this->findApps($newPath);
			}
		}
	}

	private function rrmdir(string $path): void {
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

	public function log(string $message): void {
		if ($this->verbose === 0) {
			return;
		}
		echo ' > ' . $message . PHP_EOL;
	}
}

// arguments handle
$task = '';
$usage = false;
$verbose = 0;
$returnValue = 0;
$toolName = 'translationtool';

$index = 0;
foreach ($argv as $arg) {
	$index++;
	if ($index === 1) {
		$toolName = $arg;
		continue;
	}
	switch($arg) {
		case '-h':
		case '--help':
			$usage = true;
			break;
		case '-v':
		case '--verbose':
			$verbose++;
			break;
		case 'create-pot-files':
		case 'convert-po-files':
		case 'check-files':
			$task = $arg;
			break;
		default:
			echo 'Unknown command parameter : ' . $arg . PHP_EOL;
			$usage = true;
			$returnValue = 1;
			break;
	}
}

// read the command line arguments
if(empty($task) && !$usage) {
	echo 'Missing arguments' . PHP_EOL;
	$usage = true;
	$returnValue = 1;
}

if ($usage) {
	echo 'Usage:' . PHP_EOL;
	echo ' ' . $toolName . ' <task> [<appName>]' . PHP_EOL;
	echo 'Arguments:' . PHP_EOL;
	echo ' task:            One of: create-pot-files, convert-po-files, check-files' . PHP_EOL;
	echo 'Options:'. PHP_EOL;
	echo ' -v, --verbose    Verbose mode'. PHP_EOL;
	echo ' -h, --help       Display command usage'. PHP_EOL;
	exit($returnValue);
}

$tool = new TranslationTool();
$tool->setVerbose($verbose);
if (!$tool->checkEnvironment()) {
	exit(1);
}

if ($task === 'create-pot-files') {
	$tool->createPotFiles();
} elseif ($task === 'convert-po-files') {
	$tool->convertPoFiles();
} elseif ($task === 'check-files') {
	$tool->checkFiles();
} else {
	echo 'Unknown task: "' . $task . '".' . PHP_EOL;
	exit(1);
}

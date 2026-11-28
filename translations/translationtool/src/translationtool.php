<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 Jakob Sack <nextcloud@jakobsack.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require __DIR__ . '/vendor/autoload.php';

class TranslatableApp {
	/** xgettext keywords and language for each language of languageOf() */
	private const XGETTEXT_ARGUMENTS = [
		'php' => '--keyword=t --keyword=n:1,2 --language=PHP',
		'python' => '--keyword=_ --keyword=_n:1,2 --language=Python',
		'script' => '--keyword=t:2 --keyword=n:2,3 --language=Javascript',
	];

	private string $appPath;
	private string $name = '';
	private string $fakeAppInfoFile;
	private string $fakeVueFile;
	private string $fakeLocaleFile;
	private array $ignoreFiles = [];
	private string $translationsPath;
	private TranslationTool $tool;
	/** @var array<string, array<int, array{0: string, 1: ?int, 2: 'php'|'python'|'script'|'other'}>> lines of each generated file: source file relative to the working directory, its line and the language of its strings */
	private array $fakeFileSources = [];
	/** @var array<string, array<int, list<string>>> line of each Vue template string by file, with the lines getTranslatorHintWithVueSource() reads its comment from: the line above the call and the rest of the line from the call */
	private array $vueTemplateLines = [];
	/** @var array<string, array<int, true>> lines by file whose TRANSLATORS comment getTranslatorHintWithVueSource() gives to a template string on the next line */
	private array $vueCommentLines = [];
	/** @var array<string, true> hints that getTranslatorHintWithVueSource() writes for Vue strings without a TRANSLATORS comment, which only name the source */
	private array $generatedVueHints = [];
	/** Start of a comment xgettext reads: // not after a : or a quote as in URLs, and # between whitespace, unlike #000 or #[Attribute] */
	private const LINE_COMMENT_MARKER = '(?<![:"\'])\/\/|(?<!\S)#(?!\S)';
	private const BLOCK_COMMENT_MARKER = '\/\*';
	/** Start of a comment or the * that continues a block comment */
	private const COMMENT_START = '(?:' . self::LINE_COMMENT_MARKER . '|' . self::BLOCK_COMMENT_MARKER . '|^\s*\*)';
	/** Translators addressed in any case: translator or translators at the start of a comment, translators anywhere in it, or translator before a colon */
	private const TRANSLATORS_AT_START = '\s*translators?\b';
	private const TRANSLATORS_INSIDE = '\b(?:translators\b|translator\s*:)';
	private const TRANSLATORS_COMMENT = '/' . self::COMMENT_START . '(?:' . self::TRANSLATORS_AT_START . '|(?:(?!\*\/).)*?' . self::TRANSLATORS_INSIDE . ')/ism';
	/** The same in an HTML comment of a Vue template, the only place where the extraction reads HTML comments */
	private const TEMPLATE_TRANSLATORS_COMMENT = '/<!--(?:' . self::TRANSLATORS_AT_START . '|(?:(?!-->).)*?' . self::TRANSLATORS_INSIDE . ')/ism';
	private const WHOLE_LINE_COMMENT = '/^\s*' . self::COMMENT_START . '/';
	/** Name of a translation function, also as a method like $l->t() */
	private const TRANSLATION_FUNCTION = '\b(?:t|n|_|_n)';
	/** Code that ends a translation call: a call closed on the line, or a line that closes a call over several lines */
	private const ENDED_CALL = '/' . self::TRANSLATION_FUNCTION . '\s*(?<arguments>\((?:[^()]++|(?&arguments))*\))|^\s*[)\]}]/';
	/** A colon or another translation joined to a translation call, or a colon right after a Vue mustache or PHP template tag */
	private const TEXT_OUTSIDE = '/\)\s*[.+]\s*(?:([\'"])\s*:|(?:([\'"])[^\'"]*\2\s*[.+]\s*)?[\w$>.-]*' . self::TRANSLATION_FUNCTION . '\s*\()|\}\}\s*:|\?>\s*:/';
	/** @var array<string, list<string>> lines of the source files read for the lint, by file relative to the working directory */
	private array $sourceFileLines = [];

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
		$pathToPotFile = $this->potFile();

		// Gather required data
		$this->createFakeFileForAppInfo();
		$this->createFakeFileForVueFiles();
		$this->createFakeFileForLocale();
		$translatableFiles = $this->findTranslatableFiles(
			['.php', '.js', '.jsx', '.mjs', '.html', '.ts', '.tsx', '.py'],
			['.min.js']
		);

		// Let gettext create the pot file
		$additionalArguments = ' --add-comments=TRANSLATORS --from-code=UTF-8 --package-version="3.14159" --package-name="Nextcloud" --msgid-bugs-address="translations\@example.com"';
		foreach ($translatableFiles as $entry) {
			$output = '--output=' . escapeshellarg($pathToPotFile);

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

			$xgetCmd = 'xgettext ' . $output . ' ' . $joinexisting . ' ' . self::XGETTEXT_ARGUMENTS[$this->languageOf($entry)] . ' ' . escapeshellarg($entry) . ' ' . $additionalArguments . ' ' . $extractAll . ' ' . $skipErrors;
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
		$translations = $this->loadPoFile($tmpfname);
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
				$references = $translation->getReferences()->toArray();
				if ($references !== []) {
					echo '> Starting at line ' . (reset($references)[0] ?? '') . PHP_EOL;
				}
				foreach($suspects as $suspect) {
					echo '  -> ' . $suspect . PHP_EOL;
				}
			}
		}
	}

	public function createNextcloudFiles(bool $forceEmptyTranslations = false): void {
		foreach ($this->findLanguages() as $language) {
			$poFile = $this->translationsPath . '/' . $language . '/' . $this->name . '.po';
			$translations = $this->loadPoFile($poFile);

			$strings = [];
			$plurals = $translations->getHeaders()->get(Gettext\Headers::HEADER_PLURAL) ?? 'nplurals=2; plural=(n != 1);';

			foreach ($translations as $translation) {
				if (!$forceEmptyTranslations && !$translation->isTranslated()) {
					// Skip if we have no translation (for the base form)
					continue;
				}

				if (($translation->getPlural() ?? '') !== '') {
					$identifier = $this->escape('_' . $translation->getOriginal() . '_::_' . $translation->getPlural() . '_');

					$variants = [$this->escape($translation->getTranslation() ?? '')];
					foreach ($translation->getPluralTranslations() as $plural) {
						if ($plural === '') {
							// We skip the complete string if not all plural forms exist
							continue 2;
						}
						$variants[] = $this->escape($plural);
					}

					$strings[] = $identifier . ' : [' . join(',', $variants) . ']';
				} else {
					$strings[] = $this->escape($translation->getOriginal()) . ' : ' . $this->escape($translation->getTranslation() ?? '');
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

	/**
	 * Loads a PO file without its obsolete (#~) entries, which would otherwise replace an active entry with the same msgid
	 */
	private function loadPoFile(string $poFile): Gettext\Translations {
		$content = file_get_contents($poFile);
		if ($content === false) {
			throw new RuntimeException('Could not read ' . $poFile);
		}
		$content = preg_replace('/^[ \t]*#~.*(?:\R|$)/m', '', $content);
		if ($content === null) {
			throw new RuntimeException('Could not remove the obsolete entries of ' . $poFile . ': ' . preg_last_error_msg());
		}
		// xgettext wraps a file name with spaces in Unicode isolation characters, which PoLoader would split at the spaces
		if (str_contains($content, "\u{2068}")) {
			$content = preg_replace_callback('/^#:.*$/m', static fn (array $reference): string => preg_replace_callback(
				"/\u{2068}(.*?)\u{2069}/",
				static fn (array $file): string => "\u{2068}" . rawurlencode($file[1]) . "\u{2069}",
				$reference[0],
			) ?? $reference[0], $content);
			if ($content === null) {
				throw new RuntimeException('Could not read the references of ' . $poFile . ': ' . preg_last_error_msg());
			}
		}
		return (new Gettext\Loader\PoLoader())->loadString($content);
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
		return Gettext\Generator\PoGenerator::encode($string);
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
		$sourceLines = [];
		$navigationNames = [];
		$add = function (string $string, SimpleXMLElement $node) use (&$strings, &$sourceLines): void {
			$strings[] = $string;
			$sourceLines[] = $node->getName() === '' || !function_exists('dom_import_simplexml') ? null : dom_import_simplexml($node)->getLineNo();
		};
		$xml = simplexml_load_string(file_get_contents($entryName));

		if ($xml->name) {
			$add($xml->name->__toString(), $xml->name);
		}

		if ($xml->navigations) {
			foreach ($xml->navigations as $navigation) {
				$name = $navigation->navigation->name->__toString();
				if (!in_array($name, $strings)) {
					$add($name, $navigation->navigation->name);
				}
				$navigationNames[] = $name;
			}
		}

		if ($xml->name) {
			if ($xml->name->count() > 1) {
				foreach ($xml->name as $name) {
					if ((string)$name->attributes()->lang === 'en') {
						$text = $name->__toString();
						if (!in_array($text, $strings)) {
							$add($text, $name);
						}
					}
				}
			} else {
				$name = $xml->name->__toString();
				if (!in_array($name, $strings)) {
					$add($name, $xml->name);
				}
			}
		}

		if ($xml->summary) {
			if ($xml->summary->count() > 1) {
				foreach ($xml->summary as $summary) {
					if ((string)$summary->attributes()->lang === 'en') {
						$name = $summary->__toString();
						if (!in_array($name, $strings)) {
							$add($name, $summary);
						}
					}
				}
			} else {
				$name = $xml->summary->__toString();
				if (!in_array($name, $strings)) {
					$add($name, $xml->summary);
				}
			}
		}

		if ($xml->description) {
			if ($xml->description->count() > 1) {
				foreach ($xml->description as $description) {
					if ((string)$description->attributes()->lang === 'en') {
						$name = $description->__toString();
						if (!in_array($name, $strings)) {
							$add(trim($name), $description);
						}
					}
				}
			} else {
				$name = $xml->description->__toString();
				if (!in_array($name, $strings)) {
					$add(trim($name), $xml->description);
				}
			}
		}

		$content = '';
		$line = 1;
		$this->appendToFakeFile($this->fakeAppInfoFile, $content, $line, '<?php' . PHP_EOL);
		$relativeEntryName = $this->relativePath($entryName);
		foreach ($strings as $index => $string) {
			// Nextcloud formats only the navigation names with vsprintf(), it shows the other texts as they are
			$language = in_array($string, $navigationNames, true) ? 'php' : 'other';
			$this->appendToFakeFile($this->fakeAppInfoFile, $content, $line, '$l->t(' . $this->escape($string) . ');' . PHP_EOL, $relativeEntryName, $sourceLines[$index], $language);
		}

		file_put_contents($this->fakeAppInfoFile, $content);
	}

	private function createFakeFileForVueFiles(): void {
		$fakeFileContent = '';
		$fakeFileLine = 1;
		$append = function (string $text, ?string $sourceFile = null, ?int $sourceLine = null) use (&$fakeFileContent, &$fakeFileLine): void {
			$this->appendToFakeFile($this->fakeVueFile, $fakeFileContent, $fakeFileLine, $text, $sourceFile, $sourceLine, 'script');
		};

		foreach ($this->findTranslatableFiles(['.vue']) as $vueFile) {
			$vueSource = file_get_contents($vueFile);
			if ($vueSource === false) {
				echo 'Warning: could not read ' . $vueFile . PHP_EOL;
				continue;
			}
			$relativeVuePath = $this->relativePath($vueFile);

			$matches = [];
			if (preg_match_all('/<script[^>]*>(.+)<\/script>/sU', $vueSource, $matches, PREG_OFFSET_CAPTURE)) {
				foreach ($matches[1] as [$script, $offset]) {
					$append($script, $relativeVuePath, $this->lineNumber($vueSource, $offset));
				}
				$append(";\n");
			}

			if (preg_match('/<template>(.+)<\/template>/s', $vueSource, $matches, PREG_OFFSET_CAPTURE)) {
				// Also parse the template but make sure we keep the correct line numbers for source references
				$vueSource = str_repeat("\n", substr_count($vueSource, "\n", 0, $matches[1][1]));
				$vueSource .= $matches[1][0];
				$templateLines = explode("\n", $vueSource);
				// The match starts with the character before t(, so a call at the start of a line takes its comment from the line before
				$appendTemplateCall = function (array $callMatch, string $code) use ($append, $vueFile, $vueSource, $templateLines, $relativeVuePath): void {
					[$call, $offset] = $callMatch;
					$sourceLine = $this->lineNumber($vueSource, $offset + 1);
					$this->vueTemplateLines[$relativeVuePath][$sourceLine] = [
						$templateLines[$this->lineNumber($vueSource, $offset) - 2] ?? '',
						explode("\n", substr($vueSource, $offset), 2)[0],
					];
					$append($this->getTranslatorHintWithVueSource($vueFile, $vueSource, $call));
					$append($code . PHP_EOL, $relativeVuePath, $sourceLine);
				};

				// t
				preg_match_all("/\Wt\s*\(\s*'?([\w.]+)'?,\s*'(.+)'\s*[),]/", $vueSource, $singleQuoteMatches, PREG_OFFSET_CAPTURE);
				preg_match_all("/\Wt\s*\(\s*\"?([\w.]+)\"?,\s*\"(.+)\"\s*[),]/", $vueSource, $doubleQuoteMatches, PREG_OFFSET_CAPTURE);
				preg_match_all("/\Wt\s*\(\s*'?([\w.]+)'?\s*,\s*`(.+)`\s*[),]/msU", $vueSource, $templateQuoteMatches, PREG_OFFSET_CAPTURE);
				$matches0 = array_merge($singleQuoteMatches[0], $doubleQuoteMatches[0], $templateQuoteMatches[0]);
				$matches2 = array_merge($singleQuoteMatches[2], $doubleQuoteMatches[2], $templateQuoteMatches[2]);
				foreach (array_keys($matches2) as $k) {
					$appendTemplateCall($matches0[$k], "t('" . $this->name . "', '" . preg_replace('/\s+/', ' ', $matches2[$k][0]) . "');");
				}

				// n
				preg_match_all("/\Wn\s*\(\s*'?([\w.]+)'?,\s*'(.+)'\s*,\s*'(.+)'\s*(.+)\s*[),]/", $vueSource, $singleQuoteMatches, PREG_OFFSET_CAPTURE);
				preg_match_all("/\Wn\s*\(\s*\"?([\w.]+)\"?,\s*\"(.+)\"\s*,\s*\"(.+)\"\s*(.+)\s*[),]/", $vueSource, $doubleQuoteMatches, PREG_OFFSET_CAPTURE);
				preg_match_all("/\Wn\s*\(\s*'?([\w.]+)'?\s*,\s*`(.+)`\s*,\s*`(.+)`\s*[),]/msU", $vueSource, $templateQuoteMatches, PREG_OFFSET_CAPTURE);
				$matches0 = array_merge($singleQuoteMatches[0], $doubleQuoteMatches[0], $templateQuoteMatches[0]);
				$matches2 = array_merge($singleQuoteMatches[2], $doubleQuoteMatches[2], $templateQuoteMatches[2]);
				$matches3 = array_merge($singleQuoteMatches[3], $doubleQuoteMatches[3], $templateQuoteMatches[3]);
				foreach (array_keys($matches2) as $k) {
					$appendTemplateCall($matches0[$k], "n('" . $this->name . "', '" . preg_replace('/\s+/', ' ', $matches2[$k][0]) . "', '" . preg_replace('/\s+/', ' ', $matches3[$k][0]) . "');");
				}
			}
		}

		file_put_contents($this->fakeVueFile, $fakeFileContent);
	}

	/**
	 * Lints the strings of this app that are not known yet
	 *
	 * @param Gettext\Translations $translations strings extracted from this app
	 * @param list<Gettext\Translations> $known strings that are not linted again
	 * @return list<array{level: string, message: string, file: string, line: ?int, string: string}>
	 */
	public function lintSourceStrings(SourceStringLinter $linter, Gettext\Translations $translations, array $known): array {
		$newTranslations = [];
		foreach ($translations as $translation) {
			foreach ($known as $knownTranslations) {
				$knownTranslation = $knownTranslations->find($translation->getContext(), $translation->getOriginal());
				if ($knownTranslation !== null && $knownTranslation->getPlural() === $translation->getPlural()) {
					continue 2;
				}
			}
			$newTranslations[] = $translation;
		}
		if ($newTranslations === []) {
			return [];
		}

		// A TRANSLATORS comment that reaches the wrong string is reported on that string if it is new, otherwise on the
		// string the comment follows, which for a call over several lines is on a line above the comment
		$isNew = array_fill_keys(array_map(spl_object_id(...), $newTranslations), true);
		$lines = ['hinted' => [], 'leakingHint' => [], 'foreignHint' => []];
		$stringLines = [];
		$givingLines = [];
		foreach ($translations as $translation) {
			$hints = $this->translatorHints($translation);
			foreach ($this->sourceLocations($translation) as [$file, $line]) {
				if ($line === null) {
					continue;
				}
				$stringLines[$file][$line] = true;
				if ($hints === []) {
					continue;
				}
				$lines['hinted'][$file][$line] = true;
				$givingLine = isset($this->vueTemplateLines[$file][$line]) ? null : $this->foreignHintLine($hints, $file, $line);
				if ($givingLine !== null && isset($isNew[spl_object_id($translation)])) {
					$lines['foreignHint'][$file][$line] = true;
				} elseif ($givingLine !== null) {
					$givingLines[] = [$file, $givingLine];
				}
			}
		}
		foreach ($givingLines as [$file, $givingLine]) {
			$linesAbove = array_filter(array_keys($stringLines[$file]), static fn (int $stringLine): bool => $stringLine <= $givingLine);
			if ($linesAbove !== []) {
				$lines['leakingHint'][$file][max($linesAbove)] = true;
			}
		}

		$issues = [];
		foreach ($newTranslations as $translation) {
			array_push($issues, ...$this->lintTranslation($linter, $translation, $lines));
		}
		return $issues;
	}

	/**
	 * Lints a string where it is used first, its placeholders where it is used first in each language, and each occurrence
	 *
	 * @param array{hinted: array<string, array<int, true>>, leakingHint: array<string, array<int, true>>, foreignHint: array<string, array<int, true>>} $lines see sourceFacts()
	 * @return list<array{level: string, message: string, file: string, line: ?int, string: string}>
	 */
	private function lintTranslation(SourceStringLinter $linter, Gettext\Translation $translation, array $lines): array {
		$locations = $this->sourceLocations($translation) ?: [['', null, 'other']];
		$issue = static fn (array $location, array $finding): array => [
			'level' => $finding[0],
			'message' => $finding[1],
			'file' => $location[0],
			'line' => $location[1],
			'string' => $translation->getOriginal(),
		];
		$issues = array_map(static fn (array $finding): array => $issue($locations[0], $finding), $linter->lint($translation, array_filter($locations, static fn (array $location): bool => $location[2] !== 'other') !== []));

		$firstLocations = [];
		foreach ($locations as $location) {
			$firstLocations[$location[2]] ??= $location;
		}
		$placeholderIssues = [];
		foreach ($firstLocations as $language => $location) {
			foreach ($linter->lintPlaceholders($translation, $language) as $finding) {
				$placeholderIssues[$finding[1]] ??= $issue($location, $finding);
			}
		}
		array_push($issues, ...array_values($placeholderIssues));

		foreach (array_unique($locations, SORT_REGULAR) as $location) {
			// info.xml reaches xgettext through a generated file without its comments
			if ($location[1] !== null && !$this->hasExtension($location[0], ['.xml'])) {
				foreach ($linter->lintSource($this->sourceFacts($translation, $location[0], $location[1], $lines)) as $finding) {
					$issues[] = $issue($location, $finding);
				}
			}
		}

		return $issues;
	}

	public function getName(): string {
		return $this->name;
	}

	public function extractTranslations(): Gettext\Translations {
		$this->createOrCheckPotFile();
		if (!is_file($this->potFile())) {
			return Gettext\Translations::create();
		}
		return $this->loadPoFile($this->potFile());
	}

	private function potFile(): string {
		return $this->translationsPath . '/templates/' . $this->name . '.pot';
	}

	/**
	 * Every location of a translation in the source code, with generated files resolved to the files they were generated from
	 *
	 * @return list<array{0: string, 1: ?int, 2: 'php'|'python'|'script'|'other'}> file relative to the working directory, line and the language of the string there
	 */
	private function sourceLocations(Gettext\Translation $translation): array {
		$locations = [];
		foreach ($translation->getReferences() as $file => $lines) {
			$file = (string)$file;
			if (preg_match("/^\u{2068}(.*)\u{2069}$/", $file, $matches) === 1) {
				$file = rawurldecode($matches[1]);
			}
			$isSourceFile = is_file($file);
			foreach ($lines ?: [null] as $line) {
				if (isset($this->fakeFileSources[$file][$line])) {
					$locations[] = $this->fakeFileSources[$file][$line];
				} elseif ($isSourceFile) {
					$locations[] = [$this->relativePath($file), $line, $this->languageOf($file)];
				}
			}
		}
		return $locations;
	}

	/**
	 * The extracted comments that reach translators, without the generated hint that only names the Vue file
	 *
	 * @return list<string>
	 */
	private function translatorHints(Gettext\Translation $translation): array {
		return array_values(array_filter($translation->getExtractedComments()->toArray(), fn (string $comment): bool => !isset($this->generatedVueHints[$comment])));
	}

	/**
	 * What one occurrence of a translation shows in the source code, for SourceStringLinter::lintSource()
	 *
	 * $lines holds the lines by file with a string that has a TRANSLATORS comment, with a string whose TRANSLATORS
	 * comment reaches the next string, and with a new string that gets such a comment.
	 *
	 * @param array{hinted: array<string, array<int, true>>, leakingHint: array<string, array<int, true>>, foreignHint: array<string, array<int, true>>} $lines
	 * @return array{hasTrailingHint: bool, leaksHint: bool, getsForeignHint: bool, sharesHintedLine: bool, hasMisspelledHint: bool, hasTextOutside: bool}
	 */
	private function sourceFacts(Gettext\Translation $translation, string $file, int $line, array $lines): array {
		$hasHint = $this->translatorHints($translation) !== [];
		$inTemplate = isset($this->vueTemplateLines[$file][$line]);
		$comment = $hasHint ? '' : implode("\n", $this->commentLines($file, $line));
		// xgettext merges the comments of all occurrences, so a line only counts as hinted with a TRANSLATORS comment above it
		$hasTag = str_contains($comment, 'TRANSLATORS');
		$addressesTranslators = preg_match($inTemplate ? self::TEMPLATE_TRANSLATORS_COMMENT : self::TRANSLATORS_COMMENT, $comment) === 1;
		return [
			'hasTrailingHint' => isset($this->vueCommentLines[$file][$line]),
			'leaksHint' => isset($lines['leakingHint'][$file][$line]),
			'getsForeignHint' => isset($lines['foreignHint'][$file][$line]),
			'sharesHintedLine' => $hasTag && isset($lines['hinted'][$file][$line]),
			'hasMisspelledHint' => !$hasHint && !$hasTag && $addressesTranslators,
			'hasTextOutside' => preg_match(self::TEXT_OUTSIDE, $this->linesOf($file)[$line - 1] ?? '') === 1,
		];
	}

	/**
	 * Line above the string on $line with one of its TRANSLATORS comments after the end of a translation call, which
	 * xgettext gives to the next string instead of the one it follows
	 *
	 * @param list<string> $hints the TRANSLATORS comments the string got, see translatorHints()
	 */
	private function foreignHintLine(array $hints, string $file, int $line): ?int {
		$tags = array_filter($hints, static fn (string $hint): bool => str_starts_with($hint, 'TRANSLATORS'));
		$lineAbove = $this->nonBlankLineAbove($file, $line);
		// The line above also counts when its comment start isn't recognized, like #TRANSLATORS without a space
		$candidates = $this->commentLines($file, $line) ?: ($lineAbove === null ? [] : [$lineAbove => $this->linesOf($file)[$lineAbove - 1]]);
		foreach ($candidates as $number => $candidate) {
			foreach ($tags as $tag) {
				$position = strpos($candidate, $tag);
				if ($position === false) {
					continue;
				}
				$before = substr($candidate, 0, $position);
				if ($this->followsCode($before) && preg_match(self::ENDED_CALL, $before) === 1) {
					return $number;
				}
			}
		}
		return null;
	}

	/**
	 * Whether the text has a comment start with code before it
	 */
	private function followsCode(string $text): bool {
		return preg_match('/' . self::COMMENT_START . '/', $text) === 1 && preg_match(self::WHOLE_LINE_COMMENT, $text) !== 1;
	}

	/**
	 * Line number of the nearest line above $line that isn't blank
	 */
	private function nonBlankLineAbove(string $file, int $line): ?int {
		$lines = $this->linesOf($file);
		for ($index = $line - 2; $index >= 0; $index--) {
			if (trim($lines[$index]) !== '') {
				return $index + 1;
			}
		}
		return null;
	}

	/**
	 * How Nextcloud formats the strings of a source file: with vsprintf() in PHP, with @nextcloud/l10n in JavaScript, and with Python's % in ExApps
	 *
	 * @return 'php'|'python'|'script'
	 */
	private function languageOf(string $file): string {
		return match (true) {
			$this->hasExtension($file, ['.php']) => 'php',
			$this->hasExtension($file, ['.py']) => 'python',
			default => 'script',
		};
	}

	/**
	 * Source lines a comment for the string on $line comes from
	 *
	 * In a Vue template, getTranslatorHintWithVueSource() reads the line above the call and the rest of the line from the call on. Everywhere else
	 * xgettext takes the comment that ends on the nearest non-blank line above the string, also a trailing one.
	 *
	 * @return array<int, string> the lines by line number, in a Vue template only in their order
	 */
	private function commentLines(string $file, int $line): array {
		if (isset($this->vueTemplateLines[$file][$line])) {
			return $this->vueTemplateLines[$file][$line];
		}

		$lineAbove = $this->nonBlankLineAbove($file, $line);
		if ($lineAbove === null) {
			return [];
		}
		$lines = $this->linesOf($file);
		$index = $lineAbove - 1;
		// A block comment that ends right above the string starts on the nearest line with /* above
		if (str_ends_with(rtrim($lines[$index]), '*/')) {
			for ($start = $index; $start >= 0; $start--) {
				if (str_contains($lines[$start], '/*')) {
					return array_combine(range($start + 1, $index + 1), array_slice($lines, $start, $index - $start + 1));
				}
			}
			return [];
		}
		// Code before the comment resets xgettext's comment, so only a trailing comment of that line counts
		if ($this->followsCode($lines[$index])) {
			return [$index + 1 => $lines[$index]];
		}
		if (preg_match(self::WHOLE_LINE_COMMENT, $lines[$index]) !== 1) {
			return [];
		}
		$comment = [];
		do {
			$comment = [$index + 1 => $lines[$index]] + $comment;
			$index--;
		} while ($index >= 0 && preg_match(self::WHOLE_LINE_COMMENT, $lines[$index]) === 1 && !str_ends_with(rtrim($lines[$index]), '*/'));
		// A comment after the code of the line above the block starts the block
		if ($index >= 0 && $this->followsCode($lines[$index])) {
			$comment = [$index + 1 => $lines[$index]] + $comment;
		}
		return $comment;
	}

	/**
	 * @return list<string> lines of a source file relative to the working directory, read once
	 */
	private function linesOf(string $file): array {
		return $this->sourceFileLines[$file] ??= file($this->sourceRoot() . $file, FILE_IGNORE_NEW_LINES) ?: [];
	}

	/**
	 * Appends text to a generated file, recording the source line of each of its lines when it comes from a source file
	 *
	 * @param 'php'|'python'|'script'|'other' $language how Nextcloud formats the strings of the text, see languageOf()
	 */
	private function appendToFakeFile(string $fakeFile, string &$content, int &$line, string $text, ?string $sourceFile = null, ?int $sourceLine = null, string $language = 'other'): void {
		if ($sourceFile !== null) {
			for ($offset = 0, $last = substr_count(rtrim($text, "\n"), "\n"); $offset <= $last; $offset++) {
				$this->fakeFileSources[$fakeFile][$line + $offset] ??= [$sourceFile, $sourceLine === null ? null : $sourceLine + $offset, $language];
			}
		}
		$content .= $text;
		$line += substr_count($text, "\n");
	}

	private function relativePath(string $file): string {
		$root = $this->sourceRoot();
		return str_starts_with($file, $root) ? substr($file, strlen($root)) : $file;
	}

	/**
	 * Directory that relative source paths start from
	 */
	private function sourceRoot(): string {
		return $this->tool->getRoot() . '/';
	}

	private function lineNumber(string $content, int $offset): int {
		return substr_count($content, "\n", 0, $offset) + 1;
	}

	private function getTranslatorHintWithVueSource(string $vueFile, string $content, string $translation): string {
		$relativeVuePath = substr($vueFile, strpos($vueFile, $this->name . '/src/') + strlen($this->name . '/'));

		$position = strpos($content, $translation);
		$contentBefore = substr($content, 0, $position);
		$contentAfter = substr($content, $position);
		$lineNumber = $this->lineNumber($content, $position);

		$linesBefore = explode("\n", $contentBefore);
		$linesAfter = explode("\n", $contentAfter);
		$previousLine = $linesBefore[$lineNumber - 2] ?? '';
		$currentLine = $linesAfter[0];

		// If we have a translation hint in the current line, we use it
		// This prevents mismatching hints if we have two translations
		// over consecutive lines
		// Like https://github.com/nextcloud/forms/blob/5c905b36b1ce3ca1848175d39d813581732e159d/src/views/Results.vue#L261-L263
		$readsCurrentLine = strpos($currentLine, 'TRANSLATORS') !== false;
		$searchComment = $readsCurrentLine
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
			if (!$readsCurrentLine) {
				$this->vueCommentLines[$this->relativePath($vueFile)][$lineNumber - 1] = true;
			}
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

		$hint = 'TRANSLATORS ' . $relativeVuePath . ':' . $lineNumber;
		$this->generatedVueHints[$hint] = true;
		return '// ' . $hint . PHP_EOL;
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

		$content = '';
		$line = 1;
		$this->appendToFakeFile($this->fakeLocaleFile, $content, $line, '<?php' . PHP_EOL);
		$relativeEntryName = $this->relativePath((string)realpath($entryName));
		foreach ($strings as $string) {
			$this->appendToFakeFile($this->fakeLocaleFile, $content, $line, '$l->t(' . $this->escape($string) . ');' . PHP_EOL, $relativeEntryName, null, 'php');
		}

		file_put_contents($this->fakeLocaleFile, $content);
	}

	private function deleteFakeFileForLocale(): void {
		if (is_file($this->fakeLocaleFile)) {
			unlink($this->fakeLocaleFile);
		}
	}
}

/**
 * Checks source strings against the translations page of the developer manual:
 * https://docs.nextcloud.com/server/latest/developer_manual/basics/translations.html
 */
class SourceStringLinter {
	/** Position of a printf placeholder, like the 1$ of %1$s */
	private const PRINTF_POSITION = '(?:\d+\$)?';
	/** Flags, width and precision of a printf placeholder */
	private const PRINTF_FLAGS = '(?:[-+0#]|\'.)*\d*(?:\.\d+)?';
	private const PRINTF_SPEC = self::PRINTF_FLAGS . '[bcdeEfFgGosuxX]';
	/** Every kind of placeholder, also words after a % like %uid, which some apps show literally */
	private const PLACEHOLDER = '/\{[^{}]*\}|%\([^)]*\)[a-zA-Z]|%' . self::PRINTF_POSITION . self::PRINTF_FLAGS . '[a-zA-Z]\w*%?/';
	/** A closing tag, a tag with an attribute, a self-closing tag, or an HTML element without attributes, but not a <name> placeholder */
	private const HTML_TAG = '/<\/[a-zA-Z][\w-]*\s*>|<[a-zA-Z][\w-]*\s[^<>]*=[^<>]*>|<[a-zA-Z][\w-]*\s*\/>|<(?:a|abbr|b|blockquote|br|button|code|del|div|em|h[1-6]|hr|i|img|input|ins|kbd|label|li|mark|ol|p|pre|q|s|small|span|strong|sub|sup|table|td|th|tr|u|ul)>/i';
	/** JavaScript placeholders that name a count, which needs n() as much as %d does in PHP */
	private const SCRIPT_COUNT_PLACEHOLDER = '/\{(?:count|number|num|total|amount|seconds|minutes|hours|days|weeks|months|years|\w+Count)\}/';

	/**
	 * Patterns a source string must not match, checked on the original and the plural form
	 *
	 * The text a rule checks is the raw string, the raw string only when it comes from code ('code'), the string without
	 * placeholders ('words'), or the string without placeholders, HTML tags and URLs ('prose').
	 *
	 * @var list<array{0: 'error'|'warning', 1: string, 2: 'raw'|'code'|'words'|'prose', 3: string}> level, pattern, checked text, message
	 */
	private const RULES = [
		['error', '/\|/', 'raw', 'Remove the pipe character, it breaks the translation library'],
		['error', '/[´‘’]/u', 'raw', 'Use a straight single quote (\') instead of ´, ‘ or ’'],
		['error', '/\.\.\./', 'raw', 'Use the ellipsis character (…) instead of three dots'],
		['error', '/[\t\x20\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]\x{2026}/u', 'raw', 'Use a non-breaking space (U+00A0) before the ellipsis'],
		['error', '/^\s|\s$/u', 'code', 'Remove the leading or trailing whitespace, translate the whole sentence in one string instead of concatenating it'],
		['error', "/\\b[a-z]+(?:n't|'(?:re|ll|ve|m|d))\\b|\\bcan not\\b(?! only\\b)/i", 'raw', 'Spell out contractions, for example "Do not" and "Cannot"'],
		['error', '/\\b[Ii]ds?\\b/', 'prose', 'Write "ID" in capitals'],
		['error', self::HTML_TAG, 'words', 'Remove the HTML markup and insert it through placeholders'],
		['warning', '/\\busers?\\b(?! (?:interface|agent)\\b)/i', 'prose', 'Write "account" for a profile or "people" for humans instead of "user"'],
		['warning', '/\\badmin(?:istrator)?s?\\b/i', 'prose', 'Write "administration" instead of "admin" or "administrator"'],
		['warning', '/\{\d+\}/', 'raw', 'Name the placeholder after what it holds, for example {fileName} instead of {0}'],
	];

	/** Findings for what one occurrence of a string shows in the source code, see TranslatableApp::sourceFacts() */
	private const SOURCE_RULES = [
		'hasTrailingHint' => ['error', 'Move the TRANSLATORS comment onto its own line above the string, otherwise the string on the next line gets it'],
		'leaksHint' => ['warning', 'Move the TRANSLATORS comment onto its own line above the string, otherwise the string on the next line gets it'],
		'getsForeignHint' => ['warning', 'Check the TRANSLATORS comment after the code above, xgettext gives it to this string, not to the one it follows'],
		'sharesHintedLine' => ['warning', 'Put the string on its own line with its own TRANSLATORS comment, a comment only applies to the first string of a line'],
		'hasMisspelledHint' => ['warning', 'Start the comment with TRANSLATORS in capitals, otherwise it does not reach the translators'],
		'hasTextOutside' => ['warning', 'Translate the whole text in one string, translators cannot move a colon or a joined text outside it'],
	];

	/**
	 * Checks the rules that apply to a string in every language
	 *
	 * @param bool $isFromCode whether the string is used in code, unlike the texts of info.xml, which are shown as they are
	 * @return list<array{0: 'error'|'warning', 1: string}> level and message of each finding
	 */
	public function lint(Gettext\Translation $translation, bool $isFromCode): array {
		$texts = $this->texts($translation);
		$words = preg_replace(self::PLACEHOLDER, '', $texts);
		$checked = [
			'raw' => $texts,
			'code' => $isFromCode ? $texts : [],
			'words' => $words,
			'prose' => preg_replace([self::HTML_TAG, '~\b[a-z][\w+.-]*://\S+~i'], '', $words),
		];

		$findings = [];
		foreach (self::RULES as [$level, $pattern, $text, $message]) {
			if (preg_grep($pattern, $checked[$text]) !== []) {
				$findings[] = [$level, $message];
			}
		}
		return $findings;
	}

	/**
	 * Checks the placeholders of a string as Nextcloud replaces them in one language
	 *
	 * @param 'php'|'python'|'script'|'other' $language PHP's vsprintf(), Python's %, @nextcloud/l10n, or no formatting
	 * @return list<array{0: 'error'|'warning', 1: string}> level and message of each finding
	 */
	public function lintPlaceholders(Gettext\Translation $translation, string $language): array {
		if ($language === 'other') {
			return [];
		}
		$texts = $this->texts($translation);
		$findings = $language === 'python' ? $this->lintPythonPlaceholders($texts) : $this->lintNextcloudPlaceholders($texts, $language);
		if (count($texts) === 1 && $this->hasNumberPlaceholder($texts[0], $language)) {
			$findings[] = ['warning', 'Use n() when a number in the text varies, some languages have more than one plural form'];
		}
		return $findings;
	}

	/**
	 * Checks what one occurrence of a string shows in the source code
	 *
	 * @param array<key-of<self::SOURCE_RULES>, bool> $source see TranslatableApp::sourceFacts()
	 * @return list<array{0: 'error'|'warning', 1: string}> level and message of each finding
	 */
	public function lintSource(array $source): array {
		return array_values(array_intersect_key(self::SOURCE_RULES, array_filter($source)));
	}

	/**
	 * @return list<string> the original and, for a plural, the plural form
	 */
	private function texts(Gettext\Translation $translation): array {
		$plural = $translation->getPlural() ?? '';
		return $plural !== '' ? [$translation->getOriginal(), $plural] : [$translation->getOriginal()];
	}

	/**
	 * Placeholders as Nextcloud's t() and n() replace them: %n for the count, then vsprintf() in PHP and {name} in JavaScript
	 *
	 * @param list<string> $texts
	 * @param 'php'|'script' $language
	 * @return list<array{0: 'error'|'warning', 1: string}>
	 */
	private function lintNextcloudPlaceholders(array $texts, string $language): array {
		$isPlural = count($texts) > 1;
		$findings = [];
		if (preg_grep('/%n\w/', $texts) !== []) {
			$findings[] = ['error', 'Rename the placeholder, Nextcloud replaces the %n in placeholders like %name% with the count'];
		}
		$countForms = count(preg_grep('/%n(?!\w)/', $texts));
		if ($isPlural) {
			if (preg_grep(self::PLACEHOLDER, $texts) === []) {
				$findings[] = ['error', 'Add the count (%n) to the plural, or use t() with a text that fits any number'];
			} elseif ($countForms === 0) {
				$findings[] = ['warning', 'Use %n to show the count in the plural, another placeholder can show a different number than the one that picks the plural form'];
			} elseif ($countForms === 1) {
				$findings[] = ['warning', 'Use %n in both forms, some languages use the first form for numbers like 21 too'];
			}
		} elseif ($countForms > 0) {
			$findings[] = $language === 'php'
				? ['error', 'Use n() or another placeholder, %n only works in plurals']
				: ['warning', 'Use n() for a count, in t() %n is only replaced when a count is passed'];
		}

		$printfTexts = str_replace('%%', '', $texts);
		if ($language === 'php') {
			if (preg_grep('/%(?!' . self::PRINTF_POSITION . self::PRINTF_SPEC . ')/', preg_replace('/%n\w*%?/', '', $printfTexts)) !== []) {
				$findings[] = ['error', 'Write %% for a percent sign, PHP reads every single % as the start of a placeholder'];
			}
			if (array_filter($printfTexts, $this->hasUnorderedPlaceholders(...)) !== []) {
				$findings[] = ['error', 'Use positioned placeholders (%1$s, %2$s) so translators can reorder them'];
			}
		} elseif ($language === 'script') {
			if (preg_grep('/%' . self::PRINTF_POSITION . self::PRINTF_SPEC . '(?!\w)/', $printfTexts) !== []) {
				$findings[] = ['error', 'Use {name} placeholders, JavaScript does not replace %s and %1$s'];
			}
			if (preg_grep('/\{\{|\{\s+\w*\s*\}|\{\w+\s+\}/', $texts) !== []) {
				$findings[] = ['error', 'Write the placeholder as {name}, without spaces or double braces, or it is not replaced'];
			}
		}
		return $findings;
	}

	/**
	 * Placeholders as Python's % operator replaces them
	 *
	 * @param list<string> $texts
	 * @return list<array{0: 'error'|'warning', 1: string}>
	 */
	private function lintPythonPlaceholders(array $texts): array {
		$findings = [];
		if (preg_grep('/%n/', $texts) !== []) {
			$findings[] = ['error', 'Use %(count)d for the count, Python has no %n placeholder'];
		}
		if (count($texts) > 1 && preg_grep(self::PLACEHOLDER, $texts) === []) {
			$findings[] = ['error', 'Add the count to the plural, or use _() with a text that fits any number'];
		}
		if (array_filter(str_replace('%%', '', $texts), $this->hasUnorderedPlaceholders(...)) !== []) {
			$findings[] = ['error', 'Use named placeholders like %(name)s so translators can reorder them'];
		}
		return $findings;
	}

	/**
	 * Whether translators can't reorder the printf placeholders, because more than one or not all of them have a position
	 */
	private function hasUnorderedPlaceholders(string $text): bool {
		$unpositioned = preg_match_all('/%' . self::PRINTF_SPEC . '/', $text);
		return $unpositioned > 1 || ($unpositioned === 1 && preg_match('/%\d+\$' . self::PRINTF_SPEC . '/', $text) === 1);
	}

	/**
	 * Whether a placeholder of the text holds a number, which needs n() for the plural forms of the words around it
	 *
	 * @param 'php'|'python'|'script' $language
	 */
	private function hasNumberPlaceholder(string $text, string $language): bool {
		return match ($language) {
			'php', 'python' => preg_match('/%(?:\d+\$|\(\w+\))?' . self::PRINTF_FLAGS . '[du](?!\w)/', str_replace('%%', '', $text)) === 1,
			'script' => preg_match(self::SCRIPT_COUNT_PLACEHOLDER, $text) === 1,
		};
	}
}

class TranslationTool {
	private string $translationPath;
	private array $appPaths;
	private int $verbose = 0;
	private string $root;

	/**
	 * @param ?string $root directory the apps are searched in, the working directory by default
	 */
	public function __construct(?string $root = null) {
		$this->root = $root ?? getcwd();
		$this->translationPath = $this->root . '/translationfiles';
		$this->appPaths = [];

		if (!is_dir($this->translationPath)) {
			mkdir($this->translationPath);
		}
	}

	public function setVerbose(int $verbose): void {
		$this->verbose = $verbose;
	}

	public function getRoot(): string {
		return $this->root;
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

		$this->findApps($this->root);
		if (count($this->appPaths) === 0) {
			echo 'Could not find translatable apps. Make sure that the folder "l10n" is present.' . PHP_EOL;
			return false;
		}

		return true;
	}

	public function createPotFiles(): void {
		$this->recreateTemplates();

		// iterate over all apps
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$app->createOrCheckPotFile();
		}
	}

	public function convertPoFiles(bool $forceEmptyTranslations = false): void {
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$app->createNextcloudFiles($forceEmptyTranslations);
		}
	}

	public function prepareSourceValidation(): void {
		$this->log('Generating POT files');
		$this->createPotFiles();

		$this->log('Generating en_GB PO files');
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);

			$name = basename($appPath);
			$pot = $this->translationPath . '/templates/' . $name . '.pot';
			$po = $this->translationPath . '/en_GB/' . $name . '.po';
			if (!mkdir($dir = dirname($po)) && !is_dir($dir)) {
				$this->log(sprintf('Directory "%s" was not created', $dir));
			}
			$output = [];
			exec('xgettext ' . ' -o ' . $po . ' ' . $pot, $output);
		}

		$this->log('Converting en_GB PO files to enGB.json');
		$this->convertPoFiles(true);
	}

	/**
	 * Lints the source strings of every app, only those missing in the base checkout when one is given
	 *
	 * @param string|null $basePath checkout to compare with, a string found in any of its apps is not linted
	 * @param 'text'|'github' $format plain lines or GitHub Actions annotations
	 * @return bool whether no errors were found
	 */
	public function lintSourceStrings(?string $basePath, string $format): bool {
		// A string that exists anywhere in the base is not new, also when it moved to another app
		$baseTranslations = [];
		if ($basePath !== null) {
			$base = new TranslationTool($basePath);
			$base->setVerbose($this->verbose);
			$base->findApps($basePath);
			if ($base->appPaths === []) {
				$notice = 'No translatable apps in the base checkout, all strings are linted as new';
				echo ($format === 'github' ? '::warning title=Translation source strings::' . $notice : 'warning: ' . $notice) . PHP_EOL;
			}
			$baseTranslations = array_values($base->extractTranslations());
		}

		$this->recreateTemplates();
		$linter = new SourceStringLinter();
		$counts = ['error' => 0, 'warning' => 0];
		// Apps found twice under one name share a pot file, their strings are linted once
		$lintedTranslations = [];
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$translations = $app->extractTranslations();
			$issues = $app->lintSourceStrings($linter, $translations, [...$baseTranslations, ...($lintedTranslations[$app->getName()] ?? [])]);
			$lintedTranslations[$app->getName()][] = $translations;
			foreach ($issues as ['level' => $level, 'message' => $message, 'file' => $file, 'line' => $line, 'string' => $string]) {
				$counts[$level]++;
				$finding = $message . ': "' . str_replace("\n", '\n', preg_replace('/^(.{99}).{2,}$/su', '$1…', $string)) . '"';
				if ($format === 'github') {
					$properties = ($file === '' ? '' : 'file=' . $this->escapeAnnotationProperty($file) . ',') . ($line === null ? '' : 'line=' . $line . ',');
					echo '::' . $level . ' ' . $properties . 'title=Translation source string::' . $this->escapeAnnotationData($finding) . PHP_EOL;
				} else {
					echo ($file === '' ? 'unknown location' : $file . ($line === null ? '' : ':' . $line)) . ': ' . $level . ': ' . $finding . PHP_EOL;
				}
			}
		}

		echo sprintf('%d error(s), %d warning(s) in %s source strings', $counts['error'], $counts['warning'], $basePath === null ? 'all' : 'new') . PHP_EOL;
		return $counts['error'] === 0;
	}

	/**
	 * Extracts the strings of every app
	 *
	 * @return array<string, Gettext\Translations> by app name, an app found twice under one name has the strings of both
	 */
	private function extractTranslations(): array {
		$this->recreateTemplates();
		$translations = [];
		foreach ($this->appPaths as $appPath) {
			$this->log('Application path: ' . $appPath);
			$app = new TranslatableApp($appPath, $this->translationPath, $this);
			$translations[$app->getName()] = $app->extractTranslations();
		}
		return $translations;
	}

	private function recreateTemplates(): void {
		$this->rrmdir($this->translationPath . '/templates');
		mkdir($this->translationPath . '/templates');
	}

	private function escapeAnnotationData(string $data): string {
		return str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $data);
	}

	private function escapeAnnotationProperty(string $property): string {
		return str_replace([':', ','], ['%3A', '%2C'], $this->escapeAnnotationData($property));
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
$basePath = null;
$format = null;
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
	if (str_starts_with($arg, '--base=')) {
		$basePath = substr($arg, strlen('--base='));
		continue;
	}
	if (str_starts_with($arg, '--format=')) {
		$format = substr($arg, strlen('--format='));
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
		case 'prepare-source-validation':
		case 'check-files':
		case 'lint-source-strings':
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

if ($basePath !== null && ($task !== 'lint-source-strings' || !is_dir($basePath)) && !$usage) {
	echo 'The --base option needs an existing checkout and only works with lint-source-strings' . PHP_EOL;
	$usage = true;
	$returnValue = 1;
}

$baseDirectory = $basePath === null ? '' : realpath($basePath) . '/';
$workingDirectory = realpath(getcwd()) . '/';
if ($basePath !== null && !$usage && (str_starts_with($baseDirectory, $workingDirectory) || str_starts_with($workingDirectory, $baseDirectory))) {
	echo 'The --base checkout and the working directory must not contain each other, otherwise strings are compared with themselves' . PHP_EOL;
	$usage = true;
	$returnValue = 1;
}

if ($format !== null && ($task !== 'lint-source-strings' || !in_array($format, ['text', 'github'], true)) && !$usage) {
	echo 'The --format option is either text or github and only works with lint-source-strings' . PHP_EOL;
	$usage = true;
	$returnValue = 1;
}

if ($usage) {
	echo 'Usage:' . PHP_EOL;
	echo ' ' . $toolName . ' <task> [<appName>]' . PHP_EOL;
	echo 'Arguments:' . PHP_EOL;
	echo ' task:            One of: create-pot-files, convert-po-files, prepare-source-validation, check-files, lint-source-strings' . PHP_EOL;
	echo 'Options:'. PHP_EOL;
	echo ' --base=<path>    lint-source-strings: only lint strings missing in the checkout at <path>'. PHP_EOL;
	echo ' --format=<name>  lint-source-strings: text (default) or github for GitHub Actions annotations'. PHP_EOL;
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
} elseif ($task === 'prepare-source-validation') {
	$tool->prepareSourceValidation();
} elseif ($task === 'check-files') {
	$tool->checkFiles();
} elseif ($task === 'lint-source-strings') {
	if (!$tool->lintSourceStrings($basePath, $format ?? 'text')) {
		exit(1);
	}
} else {
	echo 'Unknown task: "' . $task . '".' . PHP_EOL;
	exit(1);
}

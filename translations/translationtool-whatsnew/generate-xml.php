<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018 Morris Jobke <hey@morrisjobke.de
 *
 * @author Morris Jobke <hey@morrisjobke.de
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
include "./vendor/autoload.php";

if (count($_SERVER['argv']) !== 2) {
	echo "Please specify the path to changelog_server/data." . PHP_EOL . PHP_EOL;
	echo "    php generate-xml.php PATH/TO/changelog_server/data" . PHP_EOL;
	exit(1);
}
$path = $_SERVER['argv'][1];

if (substr($path, -1) !== '/') {
	$path .= '/';
}

if (!is_dir($path)) {
	echo "Specified path ($path) is not a directory." . PHP_EOL;
	exit(1);
}

$translationPath = $path . '../translationfiles/';

if (!is_dir($translationPath)) {
	mkdir($translationPath);
	echo "Translation files directory is not available ($translationPath)." . PHP_EOL;
	exit(1);
}

$allTranslations = getAllTranslations($translationPath);

$filePaths = glob($path . '*.xml');

$strings = [];
foreach ($filePaths as $filePath) {
	$version = basename($filePath, '.xml');
	echo "Writing changelog for $version ...\n";
	writeXMLFiles($filePath, $version, $allTranslations[$version]);
}

function getAllTranslations(string $translationPath): array
{
	$poFiles = glob($translationPath . '*/changelog_server.po');

	$allTranslations = [];

	// fill the english version
	$translations = Gettext\Translations::fromPoFile($translationPath . 'changelog_server.pot');

	$language = 'en';
	foreach ($translations as $translation) {
		/** @var \Gettext\Translation $translation */
		if (!$translation->hasTranslation()) {
			// Skip if we have no translation (for the base form)
			continue;
		}

		if ($translation->hasPlural()) {
			throw new \Exception("There should be no plural - language: $language");
		} else {
			list($version, $name, $hash) = explode('-', $translation->getId());
			// for some reason there is an EOT control character at the front of the ID - this strips it away
			$version = trim($version, pack('H*', '04'));
			if (!isset($allTranslations[$version])) {
				$allTranslations[$version] = [];
			}
			if (!isset($allTranslations[$version][$language])) {
				$allTranslations[$version][$language] = [];
			}
			$allTranslations[$version][$language][$name] = $translation->getTranslation();
		}
	}

	foreach ($poFiles as $poFile) {
		$language = basename(dirname($poFile));

		$translations = Gettext\Translations::fromPoFile($poFile);

		$strings = [];
		$plurals = $translations->getHeader('Plural-Forms');

		foreach ($translations as $translation) {
			/** @var \Gettext\Translation $translation */
			if (!$translation->hasTranslation()) {
				// Skip if we have no translation (for the base form)
				continue;
			}

			if ($translation->hasPlural()) {
				throw new \Exception("There should be no plural - language: $language");
			} else {
				list($version, $name, $hash) = explode('-', $translation->getId());
				// for some reason there is an EOT control character at the front of the ID - this strips it away
				$version = trim($version, pack('H*', '04'));
				if (!isset($allTranslations[$version])) {
					$allTranslations[$version] = [];
				}
				if (!isset($allTranslations[$version][$language])) {
					$allTranslations[$version][$language] = [];
				}
				$allTranslations[$version][$language][$name] = $translation->getTranslation();
			}
		}
	}

	return $allTranslations;
}

function writeXMLFiles(string $filePath, string $version, array $versionTranslations) {
	$data = file_get_contents($filePath);
	$xml = simplexml_load_string($data);

	// delete existing translations
	$elementsToDelete = $xml->xpath('//whatsNew[not(@lang="en")]');
	foreach($elementsToDelete as $elementToDelete)
	{
		$dom = dom_import_simplexml($elementToDelete);
		$dom->parentNode->removeChild($dom);
	}

	$sizeOfEnglishTranslations = count($versionTranslations['en']);
	// add new translations
	foreach ($versionTranslations as $language => $translations) {
		if ($language === 'en') {
			// skip
			continue;
		}

		if (count($translations)/$sizeOfEnglishTranslations < .5) {
			// skip translations with less than 50% translated strings
			echo "Skipping $version $language because it has less than 50% translated strings." . PHP_EOL;
			continue;
		}

		// sort by key to have same order as original list
		ksort($translations);

		$whatsNew = $xml->addChild('whatsNew');
		$whatsNew->addAttribute('lang', $language);
		$regular = $whatsNew->addChild('regular');
		$admin = $whatsNew->addChild('admin');

		foreach ($versionTranslations['en'] as $key => $fallbackText) {
			$text = $fallbackText;
			if (isset($translations[$key])) {
				$text = $translations[$key];
			}
			if (substr($key, 0, 7) === 'regular') {
				$regular->addChild('item', htmlspecialchars($text));
			} else {
				$admin->addChild('item', htmlspecialchars($text));
			}
		}
	}

	$dom = new DOMDocument("1.0");
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	$dom->loadXML($xml->asXML());
	file_put_contents($filePath, $dom->saveXML());
}
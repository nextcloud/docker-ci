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

if (count($_SERVER['argv']) !== 2) {
	echo "Please specify the path to changelog_server/data." . PHP_EOL . PHP_EOL;
	echo "    php extract-l10n.php PATH/TO/changelog_server/data" . PHP_EOL;
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
}

$filePaths = glob($path . '*.xml');

$strings = [];
foreach ($filePaths as $filePath) {
	$name = basename($filePath, '.xml');
	echo "Reading changelog for $name ...\n";
	$strings[$name] = fetchL10nStrings($filePath);
}

writePotFile($translationPath . '/changelog_server.pot', $strings);

function fetchL10nStrings(string $path): array
{
	$data = file_get_contents($path);
	$xml = simplexml_load_string($data);

	if ($xml === false) {
		echo "Failed loading XML: \n$data \n";
		foreach(libxml_get_errors() as $error) {
			echo $error->message . PHP_EOL;
		}
		throw new \Exception('Failed.');
	}

	$result = $xml->xpath('//whatsNew[@lang="en"]');

	$strings = [];
	$i = 1;
	foreach ($result[0]->regular->item as $item) {
		$strings["regular$i"] = (string)$item;
		$i++;
	}
	$i = 1;
	foreach ($result[0]->admin->item as $item) {
		$strings["admin$i"] = (string)$item;
		$i++;
	}

	ksort($strings);
	return $strings;
}

function writePotFile(string $path, array $strings) {
	$content = "";

	foreach ($strings as $version => $items) {
		foreach($items as $key => $item) {
			$content .= 'msgid "' . $version . '-' . $key .'-' . substr(hash('sha256', $item), 0, 8) . '"' . PHP_EOL;
			$content .= 'msgstr "' . $item . '"' . PHP_EOL . PHP_EOL;
		}
	}

	file_put_contents($path, $content);
}
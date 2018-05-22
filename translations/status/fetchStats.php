<?php

$configs = [
	[
		'url' => 'https://github.com/nextcloud/server/issues?q=is%3Aissue%20is%3Aopen%20-label%3Aenhancement%20-label%3Aspec%20-label%3Asecurity%20-label%3Abug%20-label%3Apapercut%20-label%3Aoverview%20%20-label%3A%22technical%20debt%22%20',
		'filename' => __DIR__ . '/stats',
	],
	[
		'url' => 'https://github.com/nextcloud/android/issues?q=is%3Aissue+is%3Aopen+-label%3Abug+-label%3Aenhancement+-label%3Aoverview',
		'filename' => __DIR__ . '/stats-android',
	],
];

foreach ($configs as $key => $config) {
	$body = file_get_contents($config['url']);

	$body = str_replace("\n", "", $body);

	$pattern = '!<div class="table-list-filters".*</a>!U';

	preg_match_all($pattern, $body, $matches);

	if (!isset($matches[0][0])) {
	    die('Could not find element.');
	}

	$result = preg_replace('!<.*>!U', '', $matches[0][0]);

	preg_match('!(\d+) open!i', $result, $matches);

	if (!isset($matches[1])) {
	    die('Could not find result set.');
	}
	$number = $matches[1];

	$date = (new DateTime())->format('Y-m-d');

	$fileContent = file_get_contents($config['filename']);

	if (strpos($fileContent, $date . ' ') === false) {
	    $fileContent .= PHP_EOL . $date . ' ' . $number;
	    file_put_contents($config['filename'], $fileContent);
	}

}

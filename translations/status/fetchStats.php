<?php

$today = date("Y-m-d");
$oneWeekAgo = date("Y-m-d", strtotime('-1 week'));
$twoWeeksAgo = date("Y-m-d", strtotime('-2 weeks'));

$configs = [
	[
		'url' => [
			'https://github.com/nextcloud/server/issues?q=is%3Aissue%20is%3Aopen%20-label%3Aenhancement%20-label%3Aspec%20-label%3Asecurity%20-label%3Abug%20-label%3Apapercut%20-label%3Aoverview%20%20-label%3A%22technical%20debt%22%20'
			],
		'filename' => __DIR__ . '/stats',
	],
	[
		'url' => [ 
			'enhancements' => 'https://github.com/nextcloud/android/issues?q=is%3Aissue+is%3Aopen+label%3Aenhancement+sort%3Aupdated-desc',
			'approvedBugs' => 'https://github.com/nextcloud/android/issues?q=is%3Aissue+is%3Aopen+label%3Abug+sort%3Aupdated-desc+label%3Aapproved',
			'nonApprovedBugs' => 'https://github.com/nextcloud/android/issues?utf8=%E2%9C%93&q=is%3Aissue+is%3Aopen+-label%3Aapproved+label%3Abug+',
			'untriaged' => 'https://github.com/nextcloud/android/issues?utf8=%E2%9C%93&q=is%3Aissue+is%3Aopen+-label%3Abug+-label%3Aenhancement+-label%3Aoverview'
			],
		'filename' => __DIR__ . '/stats-android',
	],
	[
	    'url' => [ 
			'oneWeekOld' => "https://github.com/nextcloud/android/pulls?utf8=%E2%9C%93&q=is%3Apr+is%3Aopen+updated%3A$oneWeekAgo..$today",
			'twoWeeksOld' => "https://github.com/nextcloud/android/pulls?utf8=%E2%9C%93&q=is%3Apr+is%3Aopen+updated%3A$twoWeeksAgo..$oneWeekAgo",
			'olderThan2Weeks' => "https://github.com/nextcloud/android/pulls?utf8=%E2%9C%93&q=is%3Apr+is%3Aopen+updated%3A2000-01-01..$twoWeeksAgo",
			],
	'filename' => __DIR__ . '/stats-android-pr',
	],
];

foreach ($configs as $key => $config) {
	$number = "";
	
	foreach ($config['url'] as $urlKey => $url) {
		$body = file_get_contents($url);

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

		$number = $number . " " . $matches[1];
	}

    $date = (new DateTime())->format('Y-m-d');

    $fileContent = file_get_contents($config['filename']);

    if (strpos($fileContent, $date . ' ') === false) {
        $fileContent .= PHP_EOL . $date . $number;
        file_put_contents($config['filename'], $fileContent);
    }
}

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require_once './vendor-bin/csfixer/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = new Config();
$config
	->setParallelConfig(ParallelConfigFactory::detect())
	->getFinder()
	->ignoreVCSIgnored(true)
	->notPath('vendor')
	->notPath('vendor-bin')
	->in(__DIR__ . '/src');
return $config;

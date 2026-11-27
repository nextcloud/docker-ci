<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$l = \OCP\Server::get(\OCP\IL10N::class);

// check-files reports a translation call inside a string, which xgettext cannot extract
$text = "See t('tests', 'Inside a string') for details";

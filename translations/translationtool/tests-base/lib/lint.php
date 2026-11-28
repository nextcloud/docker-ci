<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$l = \OCP\Server::get(\OCP\IL10N::class);

// Base checkout for the lint test: strings that already exist are not reported
$l->t('Lint already known...');
$l->n('Lint %n known file...', 'Lint %n known files...', 2);
$l->n('Lint %n folder...', 'Lint %n folder...', 2);

<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$l = \OCP\Server::get(\OCP\IL10N::class);

// TRANSLATORS PHP string with plain text
$l->t('PHP String');
// TRANSLATORS PHP string with parameters
$l->t('PHP String with %s', 'Parameter');
// TRANSLATORS PHP string with positional parameters
$l->t('PHP String with positional %1$s', [
	'Parameter',
]);

// TRANSLATORS PHP plural with plain text
$l->n('PHP %n Plural', 'PHP %n Plurals', 6);
// TRANSLATORS PHP plural with parameters
$l->n('PHP %n Plural with %s', 'PHP %n Plurals with %s', 6, [
	'Parameter',
]);
// TRANSLATORS PHP plural with positional parameters
$l->n('PHP %n Plural with positional %1$s', 'PHP %n Plurals with positional %1$s', 6, [
	'Parameter',
]);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// TRANSLATORS TS string with plain text
t('test', 'TS String')
// TRANSLATORS TS string with parameters
t('test', 'TS String with inline {parameter}', { parameter: 'Parameter' });
// TRANSLATORS TS string with wrapped parameters
t('test', 'TS String with wrapped {parameter}', {
	parameter: 'Parameter',
});

// TRANSLATORS TS plural with plain text
n('test', 'TS %n Plural', 'TS %n Plurals', 6)
// TRANSLATORS TS plural with parameters
n('test', 'TS %n Plural with %s', 'TS %n Plurals with %s', 6, { parameter: 'Parameter' });
// TRANSLATORS TS plural with wrapped parameters
n('test', 'TS %n Plural with wrapped %s', 'TS %n Plurals with wrapped %s', 6,  {
	parameter: 'Parameter',
});

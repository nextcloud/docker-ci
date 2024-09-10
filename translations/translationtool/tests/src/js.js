/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// TRANSLATORS JS string with plain text
t('test', 'JS String');
// TRANSLATORS JS string with parameters
t('test', 'JS String with inline {parameter}', { parameter: 'Parameter' });
// TRANSLATORS JS string with wrapped parameters
t('test', 'JS String with wrapped {parameter}', {
	parameter: 'Parameter',
});

// TRANSLATORS JS plural with plain text
n('test', 'JS %n Plural', 'JS %n Plurals', 6);
// TRANSLATORS JS plural with parameters
n('test', 'JS %n Plural with %s', 'JS %n Plurals with %s', 6, { parameter: 'Parameter' });
// TRANSLATORS JS plural with wrapped parameters
n('test', 'JS %n Plural with wrapped %s', 'JS %n Plurals with wrapped %s', 6,  {
	parameter: 'Parameter',
});

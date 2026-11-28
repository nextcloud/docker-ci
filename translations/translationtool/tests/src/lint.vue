<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<p>{{ t('test', 'Lint template string...') }}</p>
	<p>
		{{
t('test', 'Lint string at the start of a line...')
		}}
	</p>
	<!-- translators: Lint template hint that does not reach the translators -->
	<p>{{ t('test', 'Lint template string with a misspelled hint') }}</p>
	<a href="#translators">{{ t('test', 'Lint link to the translators section') }}</a>
	<p>{{ t('test', 'Lint headline in a template') }}:</p>
	<p>{{ t('test', 'Lint repeated template string') }}</p>
	<!-- translators: Lint hint above the second occurrence -->
	<p>{{ t('test', 'Lint repeated template string') }}</p>
	<p>{{ t('test', 'Lint template string with a hint on its line') }} <!-- TRANSLATORS Lint template hint on the line of the string --></p>
	<!-- translators: Lint misspelled hint that a blank line separates -->

	<p>{{ t('test', 'Lint template string after a blank line') }}</p>
	<p>{{ t('test', 'Lint template string with a hint for itself') }} <!-- TRANSLATORS Lint hint that also reaches the next line --></p>
	<p>{{ t('test', 'Lint template string that gets the hint of the line above') }}</p>
	<p>{{ t('test', 'Lint template string used twice') }}</p>
	<p>{{ t('test', 'Lint template string between') }} <!-- TRANSLATORS Lint hint that stays on its line --></p>
	<p>{{ t('test', 'Lint template string used twice') }}</p>
	<p>{{ t('test', 'Lint template string before one with its own hint') }} <!-- TRANSLATORS Lint hint for the first of two lines --></p>
	<p>{{ t('test', 'Lint template string with its own hint') }} <!-- TRANSLATORS Lint hint for the second of two lines --></p>

	<p>{{
t('test', 'Lint template call at the start of a line with a hint') }} <!-- TRANSLATORS Lint hint after a call at the start of a line --></p>
	<p>{{ t('test', 'Lint template string after a call at the start of a line') }}</p>
	<!-- translators: Lint misspelled hint above an attribute -->
	<NcButton :aria-label="
t('test', 'Lint template attribute at the start of a line')" />

	<!-- translators: Lint misspelled hint before a call on its line --> <p>{{ t('test', 'Lint template string after a hint on its line') }}</p>
</template>

<script>
export default {
	computed: {
		label() {
			return t('test', 'Lint script string...')
		},
		known() {
			return t('test', 'Lint known script string...')
		},
		percent() {
			// A percent sign before a word is no placeholder, placeholders like %uid are no words
			return [t('test', 'Lint 50% users'), t('test', 'Lint the %uid placeholder')]
		},
		issue() {
			const label = 'Lint issue #translators' // plain comment
			return t('test', 'Lint script string below a hash in a string')
		},
		placeholders() {
			return [
				t('test', 'Lint copied %s files', ['a']),
				t('test', 'Lint hello {0}', ['Alice']),
				n('test', 'Lint delete file from {folder}', 'Lint delete files from {folder}', 2, { folder: 'a' }),
				t('test', 'Lint hello {name}', { name: 'Alice' }),
				t('test', 'Lint deleted %n items', {}, 5),
				t('test', 'Lint hello {{name}}', { name: 'Alice' }),
				t('test', 'Lint locked for {seconds} seconds', { seconds: 5 }),
				n('test', 'Lint one item', 'Lint %n items', 2),
				t('test', /* TRANSLATORS Lint inline script hint */ 'Lint script string with an inline hint'),
				t('test', 'Lint moved %1$s to %2$s'),
				t('test', 'Lint call {phoneNumber}', { phoneNumber: '123' }),
				t('test', 'Lint script string with a hint before a comma') /* TRANSLATORS Lint script hint before a comma */,
				t('test', 'Lint script string that gets the hint before a comma'),
			]
		},
	},
}
</script>

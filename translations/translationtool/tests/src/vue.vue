<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- TRANSLATORS VUE string with plain text -->
	{{ t('test', 'VUE String') }}
	<!-- TRANSLATORS VUE string with parameters -->
	{{ t('test', 'VUE String with inline {parameter}', { parameter: 'Parameter' }) }}
	<!-- TRANSLATORS VUE string with wrapped parameters -->
	{{ t('test', 'VUE String with wrapped {parameter}', {
		parameter: 'Parameter',
	}) }}

	<!-- TRANSLATORS VUE plural with plain text -->
	{{ n('test', 'VUE %n Plural', 'VUE %n Plurals', 6) }}
	<!-- TRANSLATORS VUE plural with parameters -->
	{{ n('test', 'VUE %n Plural with %s', 'VUE %n Plurals with %s', 6, { parameter: 'Parameter' }) }}
	<!-- TRANSLATORS VUE plural with wrapped parameters -->
	{{ n('test', 'VUE %n Plural with wrapped %s', 'VUE %n Plurals with wrapped %s', 6,  {
		parameter: 'Parameter',
	}) }}

	<!-- TRANSLATORS VUE Fix for https://github.com/nextcloud/docker-ci/pull/663 - Part 1 -->
	{{ test ? t('test', 'VUE String testing') : t('test', 'VUE String not') }}

	<!-- TRANSLATORS VUE Fix for https://github.com/nextcloud/docker-ci/pull/663 - Part 2 -->
	{{ t("test", "VUE String with followup double quotes") }}: {{ data["random property"] }}
</template>

<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<!-- ensure that also vue files with both, setup and non-setup script sections can be handled. -->
<template>
	{{ t('test', 'String in template part') }}
</template>
<script setup>
const setupText = t('blahblah', 'String in "setup" script section')
</script>
<script>
import { translate as t } from '@nextcloud/l10n'

export default {
	computed: {
		text() {
			return t('blahblah', 'String in non-setup script section')
		}
	}
}
</script>

# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

# Strings against the translation guidelines, reported in expected-lint.txt
_('Lint python %n items')
_('Lint python moved %s to %s') % ('a', 'b')
_('Lint python locked for %d seconds') % 5
_n('Lint python import calendar', 'Lint python import calendars', 2)
_('Lint python string with a trailing hint')  # TRANSLATORS Lint python trailing hint
_('Lint python string that gets the trailing hint')
_('Lint python string with a hint without a space')  #TRANSLATORS Lint python hint without a space
_('Lint python string that gets the hint without a space')

# Strings following the translation guidelines
_n('Lint python %(count)d file', 'Lint python %(count)d files', 2) % {'count': 2}
_('Lint python moved %(source)s to %(target)s') % {'source': 'a', 'target': 'b'}

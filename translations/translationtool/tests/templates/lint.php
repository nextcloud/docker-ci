<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
?>
<p><?php p($l->t('Lint template string before an HTML comment')); ?></p> <!-- TRANSLATORS Lint HTML comment that xgettext does not read -->
<p><?php p($l->t('Lint template string after an HTML comment')); ?></p>
<!-- translators: Lint misspelled HTML comment that xgettext does not read either -->
<p><?php p($l->t('Lint template string below an HTML comment')); ?></p>
<div style="color: #000"><!-- translators: Lint HTML note after a CSS color --></div>
<p><?php p($l->t('Lint template string below an HTML note')); ?></p>
<a href="//example.com"><!-- translators: Lint HTML note in a link --></a>
<p><?php p($l->t('Lint template string below a link')); ?></p>
<!-- translators: Lint HTML note before a PHP comment --><?php // note ?>
<p><?php p($l->t('Lint template string below a PHP comment')); ?></p>
<p><?php p($l->t('Lint template string before a PHP hint')); /* TRANSLATORS Lint PHP hint before the end tag */ ?></p>
<p><?php p($l->t('Lint template string that gets the PHP hint')); ?></p>
<p><?php p($l->t('Lint template string before a PHP note')); // note ?></p> <!-- TRANSLATORS Lint HTML comment after a PHP note -->
<p><?php p($l->t('Lint template string after a PHP note')); ?></p>
<?php // TRANSLATORS Lint hint in a template tag ?>
<p><?php p($l->t('Lint template string below a hint in a tag')); ?></p>

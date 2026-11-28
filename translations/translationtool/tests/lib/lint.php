<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

$l = \OCP\Server::get(\OCP\IL10N::class);

// Strings against the translation guidelines, reported in expected-lint.txt
$l->t('Lint loading...');
$l->t('Lint saving …');
$l->t('Lint don’t panic');
$l->t('Lint error: ');
$l->t('Lint option A | option B');
$l->t('Lint we don\'t know');
$l->t('Lint can not connect');
$l->t('Lint unknown id {id}', ['id' => 1]);
$l->t('Lint click <b>here</b>');
$l->n('Lint import calendar', 'Lint import calendars', 2);
$l->t('Lint delete %n items');
$l->t('Lint moved %s to %s', ['a', 'b']);
$l->t('Lint ask your administrator');
$l->t('Lint invite users');
$l->n('Lint %n folder...', 'Lint %n folders...', 2);
$l->n('Lint moved %n file to %s', 'Lint moved %n files from %s to %s', 2, ['a', 'b']);
$l->t('Lint use %name% for the name', ['%name%' => 'Alice']);
$l->n('Lint delete file from %s', 'Lint delete files from %s', 2, ['a']);
$l->t('Lint moved %1$s and %s', ['a', 'b']);
$l->t('Lint locked for %d seconds', [5]);
// TRANSLATORS Lint hint that only reaches the first string of the line
$lint = [$l->t('Lint save the changes'), $l->t('Lint discard the changes')];
// Translators: Lint hint that xgettext does not extract
$l->t('Lint misspelled hint');
$l->t(
	// translators: Lint hint directly above the string of a call on several lines
	'Lint call on several lines'
);
// translators: Lint hint with a blank line before the call

$l->t('Lint string after a blank line');
$l->t('Lint progress at 50%');
/** translators: Lint docblock hint */
$l->t('Lint string after a docblock');
$l->t('Lint string with a trailing hint'); // TRANSLATORS Lint hint that xgettext gives to the next line
$l->t('Lint string that gets the trailing hint');
$l->t(/* TRANSLATORS Lint inline hint */ 'Lint string with an inline hint');
/* TRANSLATORS Lint hint before the code */ $l->t('Lint string after a hint on its line');
$l->t('Lint moved %1$s to %2$s');
$lint = [$l->t('Lint first of two strings'), $l->t(/* TRANSLATORS Lint hint for the second string */ 'Lint second of two strings')];
$l->t('Lint enter the address as <name>@<domain>');
$l->t('Lint see https://example.com/details?id=app for the app');
$l->t('Lint padded %1$\'*10s name', ['a']);
// TRANSLATORS Lint comment of two occurrences
$l->t('Lint string with the same comment twice');
$l->t('Lint string with the same comment twice'); // TRANSLATORS Lint comment of two occurrences
$l->t('Lint string that gets the comment of two occurrences');
$l->t(/* TRANSLATORS Lint inline hint before a trailing one */ 'Lint string with two hints'); // TRANSLATORS Lint trailing hint after an inline one
$l->t('Lint string that gets the trailing hint after an inline one');
$l->t('Lint string with a note before the tag'); // Note, TRANSLATORS Lint hint after a note
$l->t('Lint string that gets the hint after a note');
// Note for translators: Lint hint that xgettext does not find
$l->t('Lint string below a note for translators');
$l->t('Lint string with a hint before another comment'); /* TRANSLATORS Lint hint before another comment */ // note
$l->t('Lint string that gets the hint before another comment');
/* translators: Lint misspelled hint over
   two lines */
$l->t('Lint string below a misspelled hint over two lines');
/* TRANSLATORS Lint hint over
   two lines for the first string */
$lint = [$l->t('Lint first string below a hint over two lines'), $l->t('Lint second string below a hint over two lines')];
// Use the translator of the recipient
$l->t('Lint string below a comment about the translator');
/* Note
   for translators: Lint misspelled hint inside a block */
$l->t('Lint string below a note inside a block');
$l->t('Lint string with a hint before code') /* TRANSLATORS Lint hint before code */;
$l->t('Lint string that gets the hint before code');
/**
 * translator keep it short: Lint misspelled hint on a docblock line
 */
$l->t('Lint string below a misspelled docblock');
$lint = [
	/* TRANSLATORS Lint unit */ 'kb' => $l->t('Lint KB'),
	/* TRANSLATORS Lint unit */ 'mb' => $l->t('Lint MB'),
	'gb' => $l->t(/* TRANSLATORS Lint size */ 'Lint GB'),
	'tb' => $l->t(/* TRANSLATORS Lint size */ 'Lint TB'),
];
$l->t('Lint string with a trailing hint in a run'); // TRANSLATORS Lint hint at the start of a run
// TRANSLATORS Lint hint of the string below the run
$l->t('Lint string below a run');
$l->t('Lint string with a block hint over two lines'); /* TRANSLATORS Lint block hint
	over two lines */
$l->t('Lint string below a block hint over two lines');
$lint = $l->t(
	'Lint moved %1$s to %2$s in a call over several lines',
	['a', 'b'],
); // TRANSLATORS Lint hint after a call over several lines
$l->t('Lint string after a call over several lines');
$lint = [$l->t('Lint first string before a trailing hint'), $l->t('Lint second string before a trailing hint')]; // TRANSLATORS Lint hint after two strings
$l->t('Lint string after two strings with a trailing hint');
// Note, TRANSLATORS Lint hint after a note on its own line
$l->t('Lint string below a note with a tag');
$lint = [ // TRANSLATORS Lint hint after an opening bracket
	$l->t('Lint string after an opening bracket'),
];
$lint = $l->t( // TRANSLATORS Lint hint after an opening call
	'Lint string after an opening call',
);
$lint = $l->t('Lint string before a plain string') /* TRANSLATORS Lint hint before a plain string */ . ': ';
$l->t('Lint string that gets the hint before a plain string');
$lint = $l->t('Lint headline') . ':';
$lint = $l->t('Lint enable') . ' ' . $l->t('Lint dark mode');
$l->n('Lint one file', 'Lint %n files', 2);
$l->t('Lint you\'re done');
$l->t('Lint copy the ids');
$l->t('Lint the ‘ sign');
$l->t("Lint loading\t…");
$l->t('Lint click <a id="link">here</a>');

// Strings following the translation guidelines
$l->t('Lint loading …');
$l->t('Lint moved %1$s to %2$s', ['a', 'b']);
$l->t('Lint contact {id}', ['id' => 1]);
$l->t('Lint {user} joined', ['user' => 'Alice']);
$l->t('Lint shown as %%d for %s', ['a']);
$l->n('Lint %n file in %1$s', 'Lint %n files in %1$s', 2, ['a']);
// TRANSLATORS Lint hint on its own line
$l->t('Lint string with its own hint');
$l->t('Lint the user interface');
$l->t('Lint this can not only be fast');
// TRANSLATORS Lint hint for one occurrence of a string
$l->t('Lint string used twice');
$lint = [$l->t('Lint first string of its line'), $l->t('Lint string used twice')];
// translators: Lint comment that xgettext drops, because the call starts on the line below
$l->t(
	'Lint string below the call'
);
// translators: Lint comment above code, which xgettext drops
$lintValue = 2; // Lint trailing comment
$l->t('Lint string after a trailing comment');

// Already in the base checkout, not reported with --base
$l->t('Lint new string above a known one'); // TRANSLATORS Lint hint that reaches the known string
$l->t('Lint already known...');
$lint = $l->t(
	'Lint new string in a call above a known one',
); // TRANSLATORS Lint hint that reaches the known plural
$l->n('Lint %n known file...', 'Lint %n known files...', 2);

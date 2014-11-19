<?php

// Set correct arguments!
// Be sure to use language codes, not the path prefixes from URL! Check at admin/config/regional/language !
clone_locale_translations('FROM', 'TO');
// Example:
// clone_locale_translations('en-uk', 'en-ne');

// Do not change below this line.

/**
 * Clones existing translations (all text groups) to another language.
 *
 * Existing translations on the target language will be updated as well.
 *
 * Please don't use this function if you don't know why you need it :)
 * At least read this first: http://confluence.amazeelabs.com/x/iIG9AQ
 *
 * @param string $from
 *   Language code from which take translations.
 * @param string $to
 *   Language code to which put translations.
 */
function clone_locale_translations($from, $to) {
  $rows = db_select('locales_target', 'lt')
    ->condition('lt.language', $from)
    ->fields('lt')
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $row['language'] = $to;
    db_merge('locales_target')
      ->key(
        array(
          'lid' => $row['lid'],
          'language' => $row['language'],
          'plural' => $row['plural'],
        ))
      ->fields($row)
      ->execute();
  }
  drupal_set_message('Locale translations have been cloned (all text groups)');
}

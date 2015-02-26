//<?php

// Set correct arguments!
// Be sure to use language codes, not the path prefixes from URL! Check at admin/config/regional/language !
clone_locale_translations('FROM', 'TO', 'OVERWRITE?');
// Example not overwriting possible already existing 'en-ne' translations:
// clone_locale_translations('en-uk', 'en-ne');
// Example overwriting  already existing 'en-ne' translations:
// clone_locale_translations('en-uk', 'en-ne', TRUE);

// Do not change below this line.

/**
 * Clones existing translations (all text groups) to another language. 
 * Existing translations can either be overwritten or left intact
 *
 * Please don't use this function if you don't know why you need it :)
 * At least read this first: http://confluence.amazeelabs.com/x/iIG9AQ
 *
 * @param string $from
 *   Language code from which take translations.
 * @param string $to
 *   Language code to which put translations.
 * @param boolean $overwrite_to
 *   TRUE if the language in the $to variable should be overwritten if it 
 *   already exists.
 */
function clone_locale_translations($from, $to, $overwrite_to = FALSE) {
  $rows = db_select('locales_target', 'lt')
    ->condition('lt.language', $from)
    ->fields('lt')
    ->execute()
    ->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $row['language'] = $to;
    $query = db_merge('locales_target')
      ->key(
        array(
          'lid' => $row['lid'],
          'language' => $row['language'],
          'plural' => $row['plural'],
        ))
      ->insertFields($row);

    // If we overwrite, we just update (or insert) the fields
    // with the from translation
    if ($overwrite_to) {
      $query->updateFields($row);
    }
    // If we don't overwrite, we just update the lid which
    // actually did not change
    else {
      $query->updateFields(array('lid' => $row['lid']));
    }
    $query->execute();
  }
  drupal_set_message('Locale translations have been cloned (all text groups)');
}

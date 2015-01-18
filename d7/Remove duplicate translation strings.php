
// Should only be used with the language_fallback module 7.x-2.x version.
DuplicateStringsRemover::go();
return;

/**
 * Removes duplicated translation strings according to the language fallback
 * settings.
 */
class DuplicateStringsRemover {

  /**
   * The main method.
   */
  public static function go() {
    if (!function_exists('language_fallback_get_chain')) {
      drupal_set_message("Should only be used with the language_fallback module 7.x-2.x version!", 'error');
      return;
    }
    while (self::_go()) {}
    drupal_set_message("Don't forget to clear caches ;)");
  }

  /**
   * Removes duplicate strings. Should be called until return zero.
   *
   * @return int
   *   The number of duplicate strings that have been removed.
   */
  protected static function _go() {
    $duplicates = 0;
    $duplicates_removed = 0;
    foreach (self::getDuplicateStrings() as $langcodes => $string_keys) {
      $langcodes = explode('|', $langcodes);
      $langcodes_to_remove = self::getLanguagesToRemove($langcodes);
      $duplicates += count($langcodes) * count($string_keys);
      foreach ($langcodes_to_remove as $langcode) {
        foreach ($string_keys as $string_key) {
          self::removeString($langcode, $string_key);
          $duplicates_removed++;
        }
      }
    }
    drupal_set_message("Removed $duplicates_removed of $duplicates duplicate strings.");
    return $duplicates_removed;
  }

  /**
   * Checks which languages from the given ones could be removed safely.
   *
   * @param array $langcodes
   *
   * @return array
   *   An array of langcodes.
   */
  protected static function getLanguagesToRemove($langcodes) {
    static $chains;
    if (!isset($chains)) {
      $chains = language_list();
      foreach ($chains as $langcode => &$chain) {
        $chain = language_fallback_get_chain($langcode);
      }
      unset($chain);
    }
    $langcodes = drupal_map_assoc($langcodes);
    $current_chains = array_intersect_key($chains, $langcodes);
    foreach ($current_chains as $langcode => &$chain) {
      if (is_array($chain)) {
        $first_fallback_langcode = array_shift($chain);
        if (isset($current_chains[$first_fallback_langcode])) {
          $chain = 'to be removed';
          $current_chains[$first_fallback_langcode] = 'reserved';
        }
        else {
          $chain = 'reserved';
        }
      }
    }
    unset($chain);
    $return = array();
    foreach ($current_chains as $langcode => $result) {
      if ($result === 'to be removed') {
        $return[] = $langcode;
      }
    }
    return $return;
  }

  /**
   * Searches for the duplicate string translations.
   *
   * @return array
   *   Keys are langcodes (sorted), example: "de-ch|de|en".
   *   Values are "string keys", "{lid}|{plural}|{translation}", example:
   *   "28|0|Actual translation".
   */
  protected static function getDuplicateStrings() {
    $query = db_select('locales_target', 't');
    $query->addExpression("CONCAT(t.lid, '|', t.plural, '|', t.translation)");
    $query->addExpression("GROUP_CONCAT(t.language SEPARATOR '|')");
    $query->groupBy('t.lid')->groupBy('t.plural')->groupBy('t.translation');
    $query->having('COUNT(*) > 1');
    $result = $query->execute()->fetchAllKeyed();
    $return = array();
    foreach ($result as $string_key => $langcodes) {
      $langcodes = explode('|', $langcodes);
      sort($langcodes);
      $langcodes = implode('|', $langcodes);
      $return[$langcodes][] = $string_key;
    }

    return $return;
  }

  /**
   * Removes a particular translation.
   *
   * @param string $langcode
   * @param string $string_key
   */
  protected static function removeString($langcode, $string_key) {
    list($lid, $plural) = explode('|', $string_key);
    db_delete('locales_target')
      ->condition('language', $langcode)
      ->condition('lid', $lid)
      ->condition('plural', $plural)
      ->execute();
  }
}

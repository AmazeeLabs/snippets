<?php

clone_superfish_settings('BLOCK_DESCRIPTION', 'MENU_MACHINE_NAME', FROM_ID, TO_ID);

/**
 * Sets superfish block description/menu and clones other settings.
 *
 * Please don't use this function if you don't know why you need it :)
 * At least read this first: http://confluence.amazeelabs.com/x/iIG9AQ
 *
 * Example:
 * clone_superfish_settings('Main Menu AT', 'menu-main-menu-at', 3, 7);
 *
 * @param string $block_description
 *   Block description for admin purposes. Example: "Main Menu AT".
 * @param string $menu_id
 *   Menu machine name. Example: "menu-main-menu-at". Actual value can be found
 *   in the menu URL: admin/structure/menu/manage/{menu-machine-name}
 * @param int $from_id
 *   The ID of a superfish block from which other settings should be cloned. The
 *   actual value could be found in the block config URL:
 *   admin/structure/block/manage/superfish/{BLOCK_ID}/configure
 * @param int $to_id
 *   The ID of a superfish block to which settings will be cloned.
 */
function clone_superfish_settings($block_description, $menu_id, $from_id, $to_id) {
  $variables = db_select('variable', 'v')
    ->condition('v.name', 'superfish_%_' . $from_id, 'LIKE')
    ->fields('v', array('name', 'value'))
    ->execute()
    ->fetchAllKeyed();
  if (empty($variables)) {
    drupal_set_message('Something went wrong... Please check function arguments.', 'error');
    return;
  }
  foreach ($variables as $name => $value) {
    $name = preg_replace('#(.*)_' . $from_id .'$#', '$1_' . $to_id, $name);
    db_merge('variable')
      ->key(array('name' => $name))
      ->fields(array('value' => $value))
      ->execute();
  }
  variable_set('superfish_name_' . $to_id, $block_description);
  variable_set('superfish_menu_' . $to_id, $menu_id . ':0');
  drupal_set_message('Settings have been cloned. Please check block configuration ' . l('here', 'admin/structure/block/manage/superfish/' . $to_id . '/configure'));
}

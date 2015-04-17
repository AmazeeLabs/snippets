//<?php

// This script updates all <front> menu items (not first level, having children) to <firstchild>.

$rows_count = db_update('menu_links')
  ->condition('link_path', '<front>')
  ->condition('depth', 1, '>')
  ->condition('has_children', 1)
  ->fields(array(
    'link_path' => '<firstchild>',
    'router_path' => '<firstchild>',
  ))
  ->execute();
if ($rows_count > 0) {
  drupal_set_message("$rows_count menu items have been updated. Don't forget to clear menu cache!");
}
else {
  drupal_set_message("No menu items have been updated.");
}

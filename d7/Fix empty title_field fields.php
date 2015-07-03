<?php

$all_langcodes = array_keys(language_list());

// Find node types that are handled by entity_translation.
$vars = db_select('variable', 'v')
  ->fields('v', array('name'))
  ->condition('v.name', 'language_content_type_%', 'LIKE')
  ->condition('v.value', 's:1:"4";')
  ->execute()
  ->fetchCol();
$node_types = array();
foreach ($vars as $name) {
  $node_types[] = substr($name, 22);
}

// Get node IDs and them translation languages.
$query = db_select('entity_translation', 'et')
  ->fields('et', array('entity_id', 'language'))
  ->condition('et.entity_type', 'node')
  ->condition('et.entity_type', 'node');
$query->innerJoin('node', 'n', 'n.nid = et.entity_id');
$rows = $query->condition('n.type', $node_types, 'IN')
  ->execute()
  ->fetchAll();
$actual_langcodes = array();
foreach ($rows as $row) {
  $actual_langcodes[$row->entity_id][] = $row->language;
}

$fixed_nodes = array();

foreach ($actual_langcodes as $node_id => $required_langcodes) {
  sort($required_langcodes);
  $actual_langcodes = db_select('field_data_title_field', 'tf')
    ->fields('tf', array('language'))
    ->condition('tf.entity_type', 'node')
    ->condition('tf.entity_id', $node_id)
    ->execute()
    ->fetchCol();
  sort($actual_langcodes);

  // Compare langcodes on nodes and on title_field.
  if ($required_langcodes !== $actual_langcodes) {
    $node = node_load($node_id);

    // Get the title that will be used on missing translations.
    $title = NULL;
    if (isset($node->title_field[LANGUAGE_NONE][0]['value'])) {
      $title = $node->title_field[LANGUAGE_NONE][0]['value'];
    }
    else {
      foreach ($all_langcodes as $langcode) {
        if (isset($node->title_field[$langcode][0]['value'])) {
          $title = $node->title_field[$langcode][0]['value'];
          break;
        }
      }
    }
    if ($title === NULL) {
      echo "No title: $node_id\n";
      continue;
    }

    // Fix title_field values.
    $old_values = $node->title_field;
    $node->title_field = array();
    foreach ($required_langcodes as $langcode) {
      $node->title_field[$langcode][0]['value'] = isset($old_values[$langcode][0]['value'])
        ? $old_values[$langcode][0]['value']
        : $title;
    }
    foreach ($old_values as $langcode => $_) {
      if (!isset($node->title_field[$langcode])) {
        // The old values should be explicitly set to an empty array.
        // Otherwise they won't be removed.
        $node->title_field[$langcode] = array();
      }
    }
    try {
      node_save($node);
      $fixed_nodes[] = $node_id;
    }
    catch (Exception $e) {
      echo "Cannot save: $node_id\n";
    }
  }
}
$count = count($fixed_nodes);
echo "Fixed $count node(s)\n";
echo "Done!\n";

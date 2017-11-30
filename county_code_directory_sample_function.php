<?php

/**
 * @file
 * Example function.
 */

/**
 * Return search result human readable title & node path from search result nid.
 */
function ccd_get_path($node = NULL) {
  // Takes node object loaded by display suite and creates
  // path and human readable title text based on type.
  // Looks for *parent* field.
  // If found load it and add to the path and title.
  // Chapters will have one(?) of three different parent fields.
  // Iterate until county_code_directory_title found.
  // If node parameter unset go back to the top.
  if (!isset($node)) {
    // This seems like the desired functionality.
    drupal_goto('govt/county-code');
  }

  // Node validation.
  // Initialize variables.
  $tile = '';
  $path = '';
  $type = '';
  $ccd_index_number = '';
  $parent = '';

  // Because the fields are all different they have to be checked for
  // specifically.
  if ($node->type == 'county_code_directory_section') {
    // Don't overwrite this once it's set.
    $type = "Section";
    $ccd_index_number = $node->field_ccd_section_section_number[LANGUAGE_NONE][0]['value'];
    $ccd_index_number = str_pad($ccd_index_number, 3, '0', STR_PAD_LEFT);
    // The title to display in results. Don't overwrite it.
    $title = $node->title;
    // Build this out back to front.
    $path = '#' . $node->field_ccd_section_section_number[LANGUAGE_NONE][0]['value'];
    $parent = $node->field_ccd_section_parent_chapter[LANGUAGE_NONE][0]['target_id'];
    // We're done with the section node at this point. Unset it to make room for
    // the parent chapter.
    unset($node);

    // Good EFQ/file_attach_load tutuorial:
    // http://timonweb.com/posts/loading-only-one-field-from-an-entity-or-node-in-drupal-7/
    // Replace this with a hard coded field ids for more speed and fragility.
    $fields = field_info_instances('node', 'county_code_directory_chapter');

    // Build object for field_attach_load.
    // https://api.drupal.org/api/drupal/modules!field!field.attach.inc/function/field_attach_load/7.x#comment-59037
    $node = array(
      $parent => (object) array(
        'nid' => $parent,
        'vid' => NULL,
        'type' => 'county_code_directory_chapter',
      ),
    );

    // Load the chapter number for the ccd index number and path.
    field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_chapter_chapter_number']['field_id']));

    // Load the parent node id. Chapters can be children of parts, divisions, or
    // titles. Start with the most specific. Load and check.
    // Part.
    field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_chapter_parent_part']['field_id']));
    if (!isset($node[$parent]->field_ccd_chapter_parent_part[LANGUAGE_NONE][0]['target_id'])) {
      // Division.
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_chapter_parent_divisio']['field_id']));
      if (!isset($node[$parent]->field_ccd_chapter_parent_divisio[LANGUAGE_NONE][0]['target_id'])) {
        // Title.
        field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_chapter_parent_title']['field_id']));
      }
    }

    // Because we're only dealing with one node, and we want to match the
    // incoming syntax pull the node object off the array.
    $node = $node[$parent];
  }

  if ($node->type == 'county_code_directory_chapter') {
    // If $type is unset the search result is a chapter.
    if (!isset($type)) {
      $type = 'Chapter';
    }
    if (!isset($title)) {
      $title = $node->title;
    }

    $ccd_index_number = str_pad($node->field_ccd_chapter_chapter_number[LANGUAGE_NONE][0]['value'], 3, '0', STR_PAD_LEFT) . '.' . $ccd_index_number;
    $path = 'chapter-' . $node->field_ccd_chapter_chapter_number[LANGUAGE_NONE][0]['value'] . $path;

    if (isset($node->field_ccd_chapter_parent_part[LANGUAGE_NONE][0]['target_id'])) {
      $parent = $node->field_ccd_chapter_parent_part[LANGUAGE_NONE][0]['target_id'];
      $parent_type = 'county_code_directory_part';
      unset($node);
      $fields = field_info_instances('node', $parent_type);
      $node = array(
        $parent => (object) array(
          'nid' => $parent,
          'vid' => NULL,
          'type' => $parent_type,
        ),
      );
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_part_part_number']['field_id']));
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_part_parent_division']['field_id']));
    }
    elseif (isset($node->field_ccd_chapter_parent_divisio[LANGUAGE_NONE][0]['target_id'])) {
      $parent = $node->field_ccd_chapter_parent_divisio[LANGUAGE_NONE][0]['target_id'];
      $parent_type = 'county_code_directory_division';
      unset($node);
      $fields = field_info_instances('node', $parent_type);
      $node = array(
        $parent => (object) array(
          'nid' => $parent,
          'vid' => NULL,
          'type' => $parent_type,
        ),
      );
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_division_division_num']['field_id']));
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_division_parent_title']['field_id']));
    }
    elseif (isset($node->field_ccd_chapter_parent_title[LANGUAGE_NONE][0]['target_id'])) {
      $parent = $node->field_ccd_chapter_parent_title[LANGUAGE_NONE][0]['target_id'];
      $parent_type = 'county_code_directory_title';
      unset($node);
      $fields = field_info_instances('node', $parent_type);
      $node = array(
        $parent => (object) array(
          'nid' => $parent,
          'vid' => NULL,
          'type' => $parent_type,
        ),
      );
      field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_title_title_number']['field_id']));
    }
    else {
      // Error: Chapter with no parent.
    }

    $node = $node[$parent];
  }

  if ($node->type == 'county_code_directory_part') {
    if (!isset($type)) {
      $type = 'Part';
    }
    if (!isset($title)) {
      $title = $node->title;
    }

    $path = 'part-' . $node->field_ccd_part_part_number[LANGUAGE_NONE][0]['value'] . '/' . $path;
    $parent = $node->field_ccd_part_parent_division[LANGUAGE_NONE][0]['target_id'];

    unset($node);
    $fields = field_info_instances('node', 'county_code_directory_division');
    $node = array(
      $parent => (object) array(
        'nid' => $parent,
        'vid' => NULL,
        'type' => 'county_code_directory_division',
      ),
    );
    field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_division_division_num']['field_id']));
    field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_division_parent_title']['field_id']));

    $node = $node[$parent];
  }

  if ($node->type == 'county_code_directory_division') {
    if (!isset($type)) {
      $type = 'Division';
    }
    if (!isset($title)) {
      $title = $node->title;
    }

    $path = 'division-' . $node->field_ccd_division_division_num[LANGUAGE_NONE][0]['value'] . '/' . $path;
    $parent = $node->field_ccd_division_parent_title[LANGUAGE_NONE][0]['target_id'];

    unset($node);
    $fields = field_info_instances('node', 'county_code_directory_title');
    $node = array(
      $parent => (object) array(
        'nid' => $parent,
        'vid' => NULL,
        'type' => 'county_code_directory_title',
      ),
    );
    field_attach_load('node', $node, FIELD_LOAD_CURRENT, array('field_id' => $fields['field_ccd_title_title_number']['field_id']));

    // Because we're only dealing with one node, and we want to match the
    // incoming syntax pull the node object off the array.
    $node = $node[$parent];
  }

  if ($node->type == 'county_code_directory_title') {
    if (!isset($type)) {
      $type = 'Title';
    }
    if (!isset($title)) {
      $title = $node->title;
    }

    $ccd_index_number = str_pad($node->field_ccd_title_title_number[LANGUAGE_NONE][0]['value'], 2, '0', STR_PAD_LEFT) . '.' . $ccd_index_number;
    $path = 'title-' . $node->field_ccd_title_title_number[LANGUAGE_NONE][0]['value'] . '/' . $path;

  }

  $title = $type . ' ' . $ccd_index_number . ' ' . $title;
  return array('title' => $title, 'path' => $path);
}

<?php
/*
Plugin Name: Treadmill Comparison
Description: Use a custom table for treadmill comparisons.
Version: 1.0
Author: Jared Howard
*/

define("TREADMILL_COMPARISON_COLUMNS", 4);
define("TREADMILL_COMPARISON_TABLE", treadmill_comparison_table());
define("TREADMILL_COMPARISON_IMAGE_FOLDER", get_treadmill_comparison_image_folder());
define("TREADMILL_COMPARISON_ADMIN_URL", get_treadmill_comparison_admin_url());


/********  ADMIN AREA  ********/

function treadmill_comparison_admin_page() {
  add_options_page('Treadmill Comparison', 'Treadmill Comparison', 'manage_options', 'treadmill_comparison', 'show_treadmill_comparison_admin_main');
}
add_action('admin_menu', 'treadmill_comparison_admin_page');

function show_treadmill_comparison_admin_main() {
  $options = false;
  if (isset($_REQUEST['action'])) { $options['action'] = $_REQUEST['action']; }
  if (isset($_REQUEST['model'])) { $options['model'] = $_REQUEST['model']; }

  if (!empty($_POST) && isset($_POST['treadmill_comparison_hidden']) && $_POST['treadmill_comparison_hidden'] == 'Y') {
    $options = handle_treadmill_comparison_admin_form_submit();
  }

  echo get_treadmill_comparison_admin_main($options);
}
function get_treadmill_comparison_admin_main($options=false) {
  $output  = get_treadmill_comparison_admin_scripts();
  $output .= "\n<div class=\"wrap\">";
  $output .= "\n\t<h2>Treadmill Comparison</h2>";
  if ($options && isset($options['message'])) {
    $output .= "\n\t<div id=\"message\" class=\"updated fade\"><p>".$options['message']."</p></div>";
  }
  $output .= get_treadmill_comparison_admin_menu();
  $output .= "\n\t<div id=\"admin-treadmill-comparison-container\">";
  if ($options && isset($options['action'])) {
    if ($options['action'] == 'list') {
      $output .= get_treadmill_comparison_admin_list();
    } elseif ($options['action'] == 'add') {
      $output .= get_treadmill_comparison_admin_form();
    } elseif ($options['action'] == 'edit' && isset($options['model'])) {
      $output .= get_treadmill_comparison_admin_edit($options['model']);
    }
  }
  $output .= "</div>";
  return $output;
}

function get_treadmill_comparison_admin_scripts() {
  $output  = get_treadmill_comparison_stylesheet();
  $output .= get_treadmill_comparison_javascript();
  $output .= "<script type=\"text/javascript\">
function sendAjaxRequest_AdminTreadmillComparison(display, model) {
  new Ajax.Updater('admin-treadmill-comparison-container', '".get_option('siteurl')."/wp-admin/admin-ajax.php', {
    asynchronous:true, evalScripts:true, parameters: {
      action:'admin_treadmill_comparison_view',
      display:display,
      model:model,
      'cookie':encodeURIComponent(document.cookie)
    }
  });
}
</script>";
  return $output;
}

function get_treadmill_comparison_admin_menu() {
  $output  = "\n<ul id=\"admin-treadmill-comparison-menu\">";
  $output .= "<li><a href=\"".TREADMILL_COMPARISON_ADMIN_URL."&action=add\">Add New</a></li>";
  $output .= "<li><a href=\"".TREADMILL_COMPARISON_ADMIN_URL."&action=list\">List</a></li>";
  $output .= "</ul>";
  return $output;
}

function get_treadmill_comparison_admin_list() {
  $output .= "\n<table id=\"admin-treadmill-comparison\" cellpadding=\"0\" cellspacing=\"0\">";
  $output .= "\n\t<tr>";
  $output .= "<th>Make</td>";
  $output .= "<th>Model</td>";
  $output .= "<th>Action</td>";
  $output .= "</tr>";
  foreach (get_treadmill_comparison_all_models() as $i => $model) {
    $output .= "\n\t<tr class=\"".($i%2==0 ? 'odd' : 'even')."\">";
    $output .= "<td>".$model->make."</td>";
    $output .= "<td>".$model->model."</td>";
    $output .= "<td align=\"center\"><a href=\"".TREADMILL_COMPARISON_ADMIN_URL."&action=edit&model=".$model->id."\">Edit</a> | <a href=\"#\" onclick=\"if (confirm('Are you sure you want to destroy this treadmill?')) { sendAjaxRequest_AdminTreadmillComparison('delete', ".$model->id."); } return false;\">Delete</a></td>";
    $output .= "</tr>";
  }
  $output .= "\n</table>";
  return $output;
}

function get_treadmill_comparison_admin_edit($id) {
  if ($id) {
    $model = get_treadmill_comparison_model($id);
  }

  $output  = get_treadmill_comparison_admin_form($model);
  $output .= "\n<div id=\"admin-treadmill-comparison-model-image\"><img src=\"".get_treadmill_comparison_model_image_src($model->image)."\" /></div>";
  return $output;
}

function get_treadmill_comparison_admin_form($model=false) {
  $output  = "\n<form name=\"form1\" method=\"post\" action=\"".TREADMILL_COMPARISON_ADMIN_URL."\" enctype=\"multipart/form-data\">";
  $output .= "<input type=\"hidden\" name=\"treadmill_comparison_hidden\" value=\"Y\" />";
  if ($model) {
    $output .= "<input type=\"hidden\" name=\"comparison[id]\" value=\"".$model->id."\" />";
  }

  foreach (get_treadmill_comparison_showable_columns() as $column) {
    $field = $column->Field;
    $label = get_treadmill_comparison_display_row_name($field);
    $value = ($model) ? $model->$field : "";
    $output .= "\n<div class=\"treadmill-comparison-form-field\">";
    $output .= "<div class=\"treadmill-comparison-label-container\"><label for=\"treadmill-comparison-".$field."\">".$label."</label></div>";
    if ($field == 'image') {
      $output .= "<input id=\"treadmill-comparison-".$field."\" type=\"file\" name=\"comparison[".$field."]\" size=\"40\" />";
    } else {
      $output .= "<input id=\"treadmill-comparison-".$field."\" type=\"text\" name=\"comparison[".$field."]\" value=\"".$value."\" size=\"30\" />";
    }
    $output .= "</div>";
  }
  $output .= "\n<p class=\"submit\"><input type=\"submit\" name=\"Submit\" value=\"Save\" /></p>";
  $output .= "\n</form>";
  return $output;
}

function ajaxResponseAdminTreadmillComparisonView() {
  if (isset($_REQUEST['display'])) {
    if ($_REQUEST['display'] == 'delete') {
      delete_treadmill_comparison_model($_REQUEST['model']);
      echo get_treadmill_comparison_admin_list();
    }
  }
  exit;
}
add_action('wp_ajax_admin_treadmill_comparison_view', 'ajaxResponseAdminTreadmillComparisonView');


/********  FRONT END AREA  ********/

function show_treadmill_comparison() { echo get_treadmill_comparison(); }
function get_treadmill_comparison() {
  $output  = get_treadmill_comparison_scripts();
  $output .= "\n<div id=\"treadmill-comparison-container\">";
  $output .= get_treadmill_comparison_table();
  $output .= "\n</div>";
  return $output;
}

function get_treadmill_comparison_scripts() {
  $output  = get_treadmill_comparison_stylesheet();
  $output .= get_treadmill_comparison_javascript();
  $output .= "\n<script type=\"text/javascript\">
function sendAjaxRequest_TreadmillComparison(obj) {
  if (obj.id.match(/\-make\-/)) {
    model = $('comparison-model-' + obj.id.match(/\-make\-(.*)$/)[1]);
    if (model) {
      model.update('');
    }
  }

  new Ajax.Updater('treadmill-comparison-container', '".get_option('siteurl').get_treadmill_comparison_plugin_url()."ajax_requests.php', {
    parameters: {
      make1: $('comparison-make-1').value,
      make2: $('comparison-make-2').value,
      make3: $('comparison-make-3').value,
      make4: $('comparison-make-4').value,
      model1: ($('comparison-model-1') == null ? '' : $('comparison-model-1').value),
      model2: ($('comparison-model-2') == null ? '' : $('comparison-model-2').value),
      model3: ($('comparison-model-3') == null ? '' : $('comparison-model-3').value),
      model4: ($('comparison-model-4') == null ? '' : $('comparison-model-4').value),
      action: 'treadmill_comparison_table',
      cookie: encodeURIComponent(document.cookie)
    },
    onSuccess: function(transport){
      if (!transport.responseText) { alert('Something went wrong...'); window.location.reload(); }
    },
    onFailure: function(){ alert('Something went wrong...'); }
  });

  return false;
}
</script>";
  return $output;
}

function get_treadmill_comparison_table() {
  $output  = "\n<table id=\"comparison-table\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\">";
  $i = 1;
  foreach(get_treadmill_comparison_showable_columns() as $column) {
    $output .= get_treadmill_comparison_row($column->Field, (($i % 2 == 0) ? 'odd' : 'even'));
    $i++;
  }
  $output .= "\n</table>";
  return $output;
}

function get_treadmill_comparison_row($row_name, $row_class=false) {
  $title = get_treadmill_comparison_display_row_name($row_name);

  $output  = "\n\t<tr id=\"comparison-row-$row_name\"".($row_class ? " class=\"$row_class\"" : '').">";
  $output .= "\n\t\t<td class=\"comparison-column-title\">".($row_name == 'buy_now' ? 'Links' : $title)."</td>";
  for ($comparison_number = 1; $comparison_number <= TREADMILL_COMPARISON_COLUMNS; $comparison_number++) {
    $output .= "\n\t\t<td id=\"comparison-column-$row_name-$comparison_number\" class=\"comparison-column\">";

    $selected = (isset($_REQUEST['comparison']) && isset($_REQUEST['comparison'][$row_name]) && isset($_REQUEST['comparison'][$row_name][$comparison_number])) ? $_REQUEST['comparison'][$row_name][$comparison_number] : false;
    if ($row_name == 'make') {
      $output .= get_treadmill_comparison_select($row_name, $comparison_number, $selected);
    } elseif ($row_name == 'model') {
      $make = (isset($_REQUEST['comparison']) && isset($_REQUEST['comparison']['make']) && isset($_REQUEST['comparison']['make'][$comparison_number])) ? $_REQUEST['comparison']['make'][$comparison_number] : false;
      if ($make) {
        $output .= get_treadmill_comparison_select($row_name, $comparison_number, $selected, $make);
      }
    } else {
      if (isset($_REQUEST['comparison']) && isset($_REQUEST['comparison']['treadmill']) && isset($_REQUEST['comparison']['treadmill'][$comparison_number])) {
        $value = $_REQUEST['comparison']['treadmill'][$comparison_number]->$row_name;
        if ($row_name == 'price') { $value = (empty($value)) ? '' : '$'.number_format($value); }
        if ($row_name == 'buy_now') { $value = "<a href=\"$value\">$title</a>"; }
        if ($row_name == 'image') { $value = "<img src=\"".get_treadmill_comparison_model_image_src($value)."\" alt=\"thumbnail\" height=\"100\" />"; }
        $output .= $value;
      }
    }

    $output .= "</td>";
  }
  $output .= "\n\t</tr>";
  return $output;
}

function get_treadmill_comparison_select($row_name, $comparison_number, $selected = false, $make = false) {
  $output  = "<select id=\"comparison-$row_name-$comparison_number\" name=\"comparison-$row_name-$comparison_number\" onchange=\"sendAjaxRequest_TreadmillComparison(this);\">";
  if ($selected == false) $output .= "\n<option value=\"\">Select</option>";
  if ($row_name == 'make') {
    $output .= get_treadmill_comparison_makes_options($selected);
  } elseif ($row_name == 'model') {
    $output .= get_treadmill_comparison_models_options($make, $selected);
  }
  $output .= "</select>";
  return $output;
}

function get_treadmill_comparison_makes_options($selected = false) {
  $output  = "";
  foreach (get_treadmill_comparison_makes() as $make) {
    $make_value = $make->make;
    $output .= "\n<option value=\"".$make_value."\"".(($selected && $selected == $make_value) ? " selected=\"selected\"" : "").">".$make_value."</option>";
  }
  return $output;
}

function get_treadmill_comparison_models_options($make, $selected = false) {
  $output  = "";
  foreach (get_treadmill_comparison_models($make) as $model) {
    $model_id = $model->id;
    $model_display = $model->model;
    $output .= "\n<option value=\"".$model_id."\"".(($selected && $selected == $model_id) ? " selected=\"selected\"" : "").">".$model_display."</option>";
  }
  return $output;
}

function ajaxResponseTreadmillComparisonTable() {
  get_treadmill_comparison_values();
  return get_treadmill_comparison_table();
}


/********  COMMON  ********/

function get_treadmill_comparison_stylesheet() {
  $output  = "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"".get_option('siteurl')."/wp-content/plugins/treadmill-comparison/treadmill-comparison.css\" media=\"screen\" />";
  return $output;
}

function get_treadmill_comparison_javascript() {
  $output  = "\n<script type=\"text/javascript\" src=\"".get_option('siteurl')."/wp-content/plugins/treadmill-comparison/prototype.js\"></script>";
  return $output;
}

function get_treadmill_comparison_display_row_name($row_name) {
  return ucwords(str_replace('_', ' ', $row_name));
}

function get_treadmill_comparison_values() {
  $_REQUEST['comparison'] = array(
    'make' => array(1 => $_REQUEST['make1'], 2 => $_REQUEST['make2'], 3 => $_REQUEST['make3'], 4 => $_REQUEST['make4']),
    'model' => array(1 => $_REQUEST['model1'], 2 => $_REQUEST['model2'], 3 => $_REQUEST['model3'], 4 => $_REQUEST['model4']),
    'treadmill' => array()
  );

  foreach($_REQUEST['comparison'] as $type => $values) {
    foreach($values as $key => $value) {
      $_REQUEST['comparison'][$type][$key] = stripslashes(trim(urldecode($value)));
    }
  }

  foreach($_REQUEST['comparison']['model'] as $key => $value) {
    if (!empty($value)) {
      $_REQUEST['comparison']['treadmill'][$key] = get_treadmill_comparison_model($value);
    }
  }
}

function get_treadmill_comparison_image_folder() {
  return dirname(__FILE__).'/images';
}

function get_treadmill_comparison_plugin_url() {
  return "/wp-content/plugins/treadmill-comparison/";
}

function get_treadmill_comparison_model_image_src($image) {
  return get_treadmill_comparison_plugin_url()."images/$image";
}

function get_treadmill_comparison_admin_url() {
  $current_url = parse_url($_SERVER['REQUEST_URI']);
  $url = $current_url['path'];
  if (isset($current_url['query'])) {
    $queries = explode('&', $current_url['query']);
    foreach($queries as $key => $query) {
      if (substr($query,0,6) == 'action' || substr($query,0,5) == 'model') unset($queries[$key]);
    }
    $url .= '?'.implode('&', $queries);
  }
  return $url;
}


/********  DATABASE  ********/

function get_treadmill_comparison_columns() {
  global $wpdb;
  return $wpdb->get_results("SHOW COLUMNS FROM ".TREADMILL_COMPARISON_TABLE);
}

function get_treadmill_comparison_showable_columns() {
  $columns = array();
  foreach(get_treadmill_comparison_columns() as $column) {
    if (!in_array($column->Field, array('id','created_at','updated_at'))) {
      array_push($columns, $column);
    }
  }
  return $columns;
}

function get_treadmill_comparison_makes() {
  global $wpdb;
  return $wpdb->get_results("SELECT make FROM ".TREADMILL_COMPARISON_TABLE." GROUP BY make ORDER BY make");
}

function get_treadmill_comparison_models($make) {
  global $wpdb;
  return $wpdb->get_results("SELECT id, model FROM ".TREADMILL_COMPARISON_TABLE." WHERE make = '".$wpdb->escape($make)."' ORDER BY model");
}

function get_treadmill_comparison_model($id) {
  global $wpdb;
  return $wpdb->get_row("SELECT * FROM ".TREADMILL_COMPARISON_TABLE." WHERE id = '".$wpdb->escape($id)."'");
}

function get_treadmill_comparison_all_models() {
  global $wpdb;
  return $wpdb->get_results("SELECT * FROM ".TREADMILL_COMPARISON_TABLE." ORDER BY make, model");
}

function handle_treadmill_comparison_admin_form_submit() {
  global $wpdb;
  $options = array();
  if (isset($_POST['comparison']['id'])) {
    $model = update_treadmill_comparison_model($_POST['comparison']['id'], $_POST['comparison']);
    $options['message'] = "Treadmill was ".($model ? 'successfully' : '<strong>not</strong>')." updated.";
  } else {
    $model = insert_treadmill_comparison_model($_POST['comparison']);
    $options['message'] = "Treadmill was ".($model ? 'successfully' : '<strong>not</strong>')." added.";
  }
  if ($model) {
    if (!empty($_FILES) && $_FILES['comparison']['error']['image'] == 0) {
      $imagepath = pathinfo($_FILES['comparison']['name']['image']);
      $imagename = $model.'.'.$imagepath['extension'];
      $imagefile = TREADMILL_COMPARISON_IMAGE_FOLDER.'/'.$imagename;
      if (move_uploaded_file($_FILES['comparison']['tmp_name']['image'], $imagefile)) {
        chmod($imagefile, 0777);
        $wpdb->update(TREADMILL_COMPARISON_TABLE, array('image' => $imagename), array('id' => $model), array('%s'), array('%d'));
        $options['message'] .= " Treadmill image was successfully uploaded.";
      } else {
        $options['message'] .= " Treadmill image was <strong>not</strong> successfully uploaded.";
      }
    }
  }
  return $options;
}

function insert_treadmill_comparison_model($post_values) {
  global $wpdb;

  if (!isset($post_values['make']) || !isset($post_values['model'])) {
    return false;
  } else {
    $count = $wpdb->get_results("SELECT * FROM ".TREADMILL_COMPARISON_TABLE." WHERE make = '".$post_values['make']."' AND model = '".$post_values['model']."'");
    if (!empty($count)) {
      return false;
    } else {
      $insert_array = create_treadmill_comparison_database_model_array($post_values);
      $insert_array['values']['created_at'] = date('Y-m-d H:i:s');
      array_push($insert_array['types'], "%s");
      $insert = $wpdb->insert(TREADMILL_COMPARISON_TABLE, $insert_array['values'], $insert_array['types']);
      return ($insert) ? $wpdb->insert_id : false;
    }
  }
}

function update_treadmill_comparison_model($id, $post_values) {
  global $wpdb;
  $update_array = create_treadmill_comparison_database_model_array($post_values);
  $update = $wpdb->update(TREADMILL_COMPARISON_TABLE, $update_array['values'], array('id' => $id), $update_array['types'], array('%d'));
  return ($update) ? $id : false;
}

function create_treadmill_comparison_database_model_array($post_values) {
  $values = array('updated_at' => date('Y-m-d H:i:s'));
  $types = array("%s");
  foreach(get_treadmill_comparison_showable_columns() as $column) {
    if (isset($post_values[$column->Field])) {
      $value = stripslashes($post_values[$column->Field]);
      $values[$column->Field] = $value;
      array_push($types, ($column->Field == 'price' ? "%d" : "%s"));
    }
  }
  return array('values' => $values, 'types' => $types);
}

function delete_treadmill_comparison_model($id) {
  global $wpdb;
  $wpdb->get_results("DELETE FROM ".TREADMILL_COMPARISON_TABLE." WHERE id = '".$wpdb->escape($id)."'");
}

function treadmill_comparison_install() {
  global $wpdb;

  if (!file_exists(TREADMILL_COMPARISON_IMAGE_FOLDER)) {
    mkdir(TREADMILL_COMPARISON_IMAGE_FOLDER, 0777);
    chmod(TREADMILL_COMPARISON_IMAGE_FOLDER, 0777);
  }

  if ($wpdb->get_var("SHOW TABLES LIKE '".TREADMILL_COMPARISON_TABLE."'") != TREADMILL_COMPARISON_TABLE) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $sql = "CREATE TABLE ".TREADMILL_COMPARISON_TABLE." (
  id int(11) NOT NULL AUTO_INCREMENT,
  created_at datetime,
  updated_at datetime,
  make varchar(255) NOT NULL,
  model varchar(255) NOT NULL,
  image varchar(255) NULL,
  incline varchar(255) NULL,
  weight_capacity varchar(255) NULL,
  heart_rate_monitor varchar(255) NULL,
  workout_programs varchar(255) NULL,
  running_area varchar(255) NULL,
  motor varchar(255) NULL,
  speed_range varchar(255) NULL,
  warranty varchar(255) NULL,
  notes text NULL,
  price decimal(12,0) NULL,
  buy_now text NULL,
  PRIMARY KEY (id),
  KEY index_treadmill_comparison_on_created_at (created_at),
  KEY index_treadmill_comparison_on_model (model),
  KEY index_treadmill_comparison_on_make (make)
);";
    dbDelta($sql);
  }
}
register_activation_hook(__FILE__, 'treadmill_comparison_install');

function treadmill_comparison_table() {
  global $wpdb;
  return $wpdb->prefix."treadmill_comparison";
}

?>
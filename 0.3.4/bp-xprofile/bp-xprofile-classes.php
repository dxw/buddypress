<?php

/**************************************************************************
 PLUGIN CLASSES
 --------------------------------------------------------------------------
   - BP_XProfile_Group -- Profile group management
   - BP_XProfile_Field -- Profile field management
   - BP_XProfile_ProfileData -- Profile data management
   - BP_XProfile_Picture -- Profile picture management
 -------------------------------------------------------------------------- 
 **************************************************************************/

Class BP_XProfile_Group {
	var $id = null;
	var $name;
	var $description;
	var $can_delete;
	var $fields;
	
	var $table_name_groups;
	var $table_name_fields;
	
	function bp_xprofile_group( $id = null ) {
		global $bp_xprofile_table_name_groups, $bp_xprofile_table_name_fields;
 
		$this->table_name_groups = $bp_xprofile_table_name_groups;
		$this->table_name_fields = $bp_xprofile_table_name_fields;
		
		if ( $id ) {
			if ( bp_core_validate($id) ) {
				$this->populate($id);
			}
		}
	}
	
	function populate( $id ) {
		global $wpdb;
		
		$sql = $wpdb->prepare("SELECT * FROM $this->table_name_groups WHERE id = %d", $id);

		if ( $group = $wpdb->get_row($sql) ) {
			$this->id = $group->id;
			$this->name = $group->name;
			$this->description = $group->description;
			$this->can_delete = $group->can_delete;
			
			// get the fields for this group.
			$this->fields = $this->get_fields();
		}

	}

	function save() {
		global $wpdb;

		if ( $this->id != null ) {
			$sql = $wpdb->prepare("UPDATE $this->table_name_groups SET name = %s, description = %s WHERE id = %d", $this->name, $this->description, $this->id);
		} else {
			$sql = $wpdb->prepare("INSERT INTO $this->table_name_groups (name, description, can_delete) VALUES (%s, %s, 1)", $this->name, $this->description);		
		}
		
		if ( $wpdb->query($sql) === false )
			return false;
		
		return true;
	}
	
	function delete() {
		global $wpdb;
		
		if ( !$this->can_delete )
			return false;
		
		$sql = $wpdb->prepare("DELETE FROM $this->table_name_groups WHERE id = %d", $this->id);

		if ( $wpdb->query($sql) === false) {
			return false;
		} else {
			// Now the group is deleted, remove the group's fields.
			if ( BP_XProfile_Field::delete_for_group($this->id) ) {
				// Now delete all the profile data for the groups fields
				for ( $i = 0; $i < count($this->fields); $i++ ) {	
					BP_XProfile_ProfileData::delete_for_field($this->fields[$i]->id);
				}
			}
			
			return true;
		}
	}
	
	function get_fields() {
		global $wpdb;

		// Get field ids for the current group.
		$sql = $wpdb->prepare("SELECT id, type FROM $this->table_name_fields WHERE group_id = %d AND parent_id = 0 ORDER BY id", $this->id);

		if(!$fields = $wpdb->get_results($sql))			
			return false;

		return $fields;
	}
	
	function render_admin_form() {
		global $message;

		if ( $this->id == null ) {
			$title = __('Add Group');
			$action = "admin.php?page=xprofile_settings&amp;mode=add_group";
		} else {
			$title = __('Edit Group');
			$action = "admin.php?page=xprofile_settings&amp;mode=edit_group&amp;group_id=" . $this->id;			
		}
	?>
		<div class="wrap">
		
			<h2><?php echo $title; ?></h2>
			<br />
			
			<?php
				if ( $message != '' ) {
					$type = ( $type == 'error' ) ? 'error' : 'updated';
			?>
				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo $message; ?></p>
				</div>
			<?php } ?>
			
			<form action="<?php echo $action; ?>" method="post">
				
				<div id="titlediv">
					<label for="group_name"><?php _e("Group Name") ?></label>
					<div>
						<input type="text" name="group_name" id="group_name" value="<?php echo $this->name ?>" style="width:50%" />
					</div>
				</div>
				
				<p class="submit" style="text-align: left">
					<input type="submit" name="saveGroup" value="<?php echo $title; ?> &raquo;" />
				</p>
			
			</form>
		</div>
		
		<?php
	}
	
	/** Static Functions **/
	
	function get_all( $hide_empty = false ) {
		global $wpdb, $bp_xprofile_table_name_groups, $bp_xprofile_table_name_fields;

		if ( $hide_empty ) {
			$sql = $wpdb->prepare("SELECT DISTINCT g.* FROM $bp_xprofile_table_name_groups g INNER JOIN $bp_xprofile_table_name_fields f ON g.id = f.group_id ORDER BY g.id ASC");
		} else {
			$sql = $wpdb->prepare("SELECT * FROM $bp_xprofile_table_name_groups ORDER BY id ASC");
		}

		if ( !$groups_temp = $wpdb->get_results($sql) )
			return false;
			
		for ( $i = 0; $i < count($groups_temp); $i++ ) {
			$group = new BP_XProfile_Group($groups_temp[$i]->id);
			$groups[] = $group;
		}

		return $groups;
	}
	
	function admin_validate() {
		global $message;
		
		// Validate Form
		if ( $_POST['group_name'] == '' ) {
			$message = __('Please make sure you give the group a name.');
			return false;
		} else {
			return true;
		}
	}
}


Class BP_XProfile_Field {
	var $id;
	var $group_id;
	var $parent_id;
	var $type;
	var $name;
	var $desc;
	var $is_required;
	var $can_delete;
	var $field_order;
	var $option_order;
	var $order_by;
	var $is_default_option;
	
	var $data;
	var $message = null;
	var $message_type = 'err';
	
	var $table_name_groups;
	var $table_name_fields;

	function bp_xprofile_field( $id = null, $user_id = null, $get_data = true ) {
		global $bp_xprofile_table_name_groups, $bp_xprofile_table_name_fields,$wpdb;
		
		$this->table_name_groups = $bp_xprofile_table_name_groups;
		$this->table_name_fields = $bp_xprofile_table_name_fields;	
		
		if ( $id ) {
			$this->populate( $id, $user_id, $get_data );
		}
	}
	
	function populate( $id, $user_id, $get_data ) {
		global $wpdb, $userdata;
		
		if ( is_null($user_id) ) {
			$user_id = $userdata->ID;
		}
		
		$sql = $wpdb->prepare("SELECT * FROM $this->table_name_fields WHERE id = %d", $id);
	
		if ( $field = $wpdb->get_row($sql) ) {
			$this->id = $field->id;
			$this->group_id = $field->group_id;
			$this->parent_id = $field->parent_id;
			$this->type = $field->type;
			$this->name = stripslashes($field->name);
			$this->desc = stripslashes($field->description);
			$this->is_required = $field->is_required;
			$this->is_public= $field->is_public;
			$this->can_delete = $field->can_delete;
			$this->field_order = $field->field_order;
			$this->option_order = $field->option_order;
			$this->order_by = $field->order_by;
			$this->is_default_option = $field->is_default_option;

			if ( $get_data ) {
				$this->data = $this->get_field_data($user_id);
			}
		}
	}

	function delete() {
		global $wpdb;
		
		if ( !$this->id )
			return false;
			
		$sql = $wpdb->prepare("DELETE FROM $this->table_name_fields WHERE id = %d OR parent_id = %d", $this->id, $this->id);

		if ( $wpdb->query($sql) === false )
			return false;
		
		// delete the data in the DB for this field
		BP_XProfile_ProfileData::delete_for_field($this->id);
		
		return true;
	}
	
	function save() {
		global $wpdb;
		
		if ( $this->id != null ) {
			$sql = $wpdb->prepare("UPDATE $this->table_name_fields SET group_id = %d, parent_id = 0, type = %s, name = %s, description = %s, is_required = %d, is_public = %d, order_by = %s WHERE id = %d", $this->group_id, $this->type, $this->name, $this->desc, $this->is_required, $this->is_public, $this->order_by, $this->id);
		} else {
			$sql = $wpdb->prepare("INSERT INTO $this->table_name_fields	(group_id, parent_id, type, name, description, is_required, is_public, order_by) VALUES (%d, 0, %s, %s, %s, %d, %d, %s)", $this->group_id, $this->type, $this->name, $this->desc, $this->is_required, $this->is_public, $this->order_by);
		}
		if ( $wpdb->query($sql) !== false ) {
			// Only do this if we are editing an existing field
			if ( $this->id != null ) {
				// Remove any radio or dropdown options for this
				// field. They will be re-added if needed.
				// This stops orphan options if the user changes a
				// field from a radio button field to a text box. 
				$this->delete_children();
			}
			
			// Check to see if this is a field with child options.
			// We need to add the options to the db, if it is.
			if ( $this->type == 'radio' || $this->type == 'selectbox' || $this->type == 'checkbox' || $this->type == 'multiselectbox' ) {
				if ( $this->id ) {
					$parent_id = $this->id;
				} else {
					$parent_id = $wpdb->insert_id;	
				}
				
				if ( !empty( $_POST['field_file'] ) ) {
					// Add a prebuilt field from a csv file
					$field_file = $_POST['field_file'];
					
					if ( $fp = fopen($field_file, 'r') ) {
						$start_reading = false;

						while ( ! feof($fp) && !$start_reading) {
							if ( $s = fgets ($fp, 1024) ) {
								if ( preg_match ( '/\*\//', $s ) ) {
										$start_reading = true;
								}
							}								
						}

						while ( ( $data = fgetcsv( $fp ) ) ) {
							$num = count($data);
							$name = '';
							$description = '';
							
							if ( $num >= 1 )
								$name = $data[0];
							
							if ( $num >= 2 )
								$description = $data[1];
								
							if ( $num > 0 ) {
								$sql = $wpdb->prepare( "INSERT INTO $this->table_name_fields (group_id, parent_id, type, name, description, is_required, option_order) VALUES (%d, %d, 'option', %s, %s, 0, %d)", $this->group_id, $parent_id, $name, $description, $option_order);
								$wpdb->query($sql);
							}
						}
						fclose($fp);
					}
				} else {
					if ( $this->type == "radio" ) {
						$options = $_POST['radio_option'];
						$defaults = $_POST['isDefault_radio_option'];
					} else if ( $this->type == "selectbox" ) {
						$options = $_POST['selectbox_option'];
						$defaults = $_POST['isDefault_selectbox_option'];
					} else if ( $this->type == "multiselectbox" ) {
						$options = $_POST['multiselectbox_option'];
						$defaults = $_POST['isDefault_multiselectbox_option'];
					} else if ( $this->type == "checkbox" ) {
						$options = $_POST['checkbox_option'];
						$defaults = $_POST['isDefault_checkbox_option'];
					}
					
					$counter = 1;
					if ( count($options) > 0 ) {
						foreach ( $options as $option_key => $option_value ) {
							$is_default = 0;

							if ( is_array($defaults) ) {
								if ( isset($defaults[$option_key]) )
									$is_default = 1;
							} else {
								if ( $defaults == $option_key )
									$is_default = 1;
							}

							if ( $option_value != "" ) { 
								// don't insert an empty option.
								$sql = $wpdb->prepare("INSERT INTO $this->table_name_fields	(group_id, parent_id, type, name, description, is_required, option_order, is_default_option) VALUES (%d, %d, 'option', %s, '', 0, %d, %d)", $this->group_id, $parent_id, $option_value, $counter, $is_default);

								if ( $wpdb->query($sql) === false ) {
									return false;

									// @TODO 
									// Need to go back and reverse what has been entered here.
								}
							}
						
							$counter++;
						}					
					}
				}

				return true;
			
			} else {
				return true;
			}
		}
		else
		{
			return false;
		}
	}
	
	function get_edit_html( $value = null ) {
		global $bp_xprofile_image_base;
		
		$asterisk = '';
		if ( $this->is_required ) {
			$asterisk = '* ';
		}
		
		$error_class = '';
		if ( $this->message ) {
			$this->message = '<p class="' . $this->message_type . '">' . $this->message . '</p>';
			$message_class = ' class="' . $this->message_type . '"';
		}
		
		if ( !is_null($value) ) {
			$this->data->value = $value;
		}
		
		switch ( $this->type ) {
			case 'textbox':
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<input type="text" name="field_' . $this->id . '" id="field_' . $this->id . '" value="' . $this->data->value . '" />';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'textarea':
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<textarea rows="5" cols="40" name="field_' . $this->id . '" id="field_' . $this->id . '">' . $this->data->value . '</textarea>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'selectbox':
				$options = $this->get_children();
				
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<select name="field_' . $this->id . '" id="field_' . $this->id . '">';
				
				$html .= '<option value="">--------</option>';	
				for ( $k = 0; $k < count($options); $k++ ) {
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);

					if ( $option_value == $options[$k]->name || $value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' selected="selected"';
					} else {
						$selected = '';
					}
					
					$html .= '<option' . $selected . ' value="' . $options[$k]->name . '">' . $options[$k]->name . '</option>';
				}
				
				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'multiselectbox':
				$options = $this->get_children();
				
				$html .= '<div class="signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '">' . $asterisk . $this->name . ':</label>';
				$html .= $this->message . '<select class="multi-select" multiple="multiple" name="field_' . $this->id . '[]" id="field_' . $this->id . '">';
					for ( $k = 0; $k < count($options); $k++ ) {
						$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);
						$values = explode( ',', $option_value );
						
						if ( $option_value == $options[$k]->name || $value == $options[$k]->name || in_array( $options[$k]->name, $values ) || ( $options[$k]->is_default_option ) ) {
							$selected = ' selected="selected"';
						} else {
							$selected = '';
						}
						
						$html .= '<option' . $selected . ' value="' . $options[$k]->name . '">' . $options[$k]->name . '</option>';
					}
				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
			break;
			
			case 'radio':
				$options = $this->get_children();
				
				$html .= '<div class="radio signup-field" id="field_' . $this->id . '"><span class="signup-label">' . $asterisk . $this->name . ':</span>' . $this->message;
				for ( $k = 0; $k < count($options); $k++ ) {
					
					$option_value = BP_XProfile_ProfileData::get_value_byid($options[$k]->parent_id);
				
					if ( $option_value == $options[$k]->name || $value == $options[$k]->name || $options[$k]->is_default_option ) {
						$selected = ' checked="checked"';
					} else {
						$selected = '';
					}
					
					$html .= '<label><input' . $selected . ' type="radio" name="field_' . $this->id . '" id="option_' . $options[$k]->id . '" value="' . $options[$k]->name . '"> ' . $options[$k]->name . '</label>';
				}
				
				$html .= '<span class="signup-description">' . $this->desc . '</span>';				
				$html .= '</div>';
				
				if ( !$this->is_required ) {
					$html .= '<a href="javascript:clear(\'field_' . $this->id . '\');"><img src="' . $bp_xprofile_image_base . '/cross.gif" alt="Clear" /> Clear</a>';
				}
				
			break;
			
			case 'checkbox':
				$value = explode( ",", $value );
				
				$options = $this->get_children();
				
				$html .= '<div class="checkbox signup-field" id="field_' . $this->id . '"><span class="signup-label">' . $asterisk . $this->name . ':</span>' . $this->message;
				
				$option_values = BP_XProfile_ProfileData::get_value_byid($options[0]->parent_id);
				$option_values = unserialize($option_values);
				
				for ( $k = 0; $k < count($options); $k++ ) {	
					for ( $j = 0; $j < count($option_values); $j++ ) {
						if ( $option_values[$j] == $options[$k]->name || @in_array( $options[$k]->name, $value ) || $options[$k]->is_default_option ) {
							$selected = ' checked="checked"';
							break;
						}
					}
					
					$html .= '<label><input' . $selected . ' type="checkbox" name="field_' . $this->id . '[]" id="field_' . $options[$k]->id . '_' . $k . '" value="' . $options[$k]->name . '"> ' . $options[$k]->name . '</label>';
					$selected = '';
				}
				
				$html .= '<span class="signup-description">' . $this->desc . '</span>';				
				$html .= '</div>';
				
			break;
			
			case 'datebox':
				if ( $this->data->value != '' ) {
					$day = date("j", $this->data->value);
					$month = date("F", $this->data->value);
					$year = date("Y", $this->data->value);
					$default_select = ' selected="selected"';
				}
				
				$html .= '<div id="field_' . $this->id . '" class="datefield signup-field">';
				$html .= '<label class="signup-label" for="field_' . $this->id . '_day">' . $asterisk . $this->name . ':</label>';
				
				$html .= $this->message . '
				<select name="field_' . $this->id . '_day" id="field_' . $this->id . '_day">';
				$html .= '<option value=""' . $default_select . '>--</option>';
				
				for ( $i = 1; $i < 32; $i++ ) {
					if ( $day == $i ) { 
						$selected = ' selected = "selected"'; 
					} else {
						$selected = '';
					}
					$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
				}
				
				$html .= '</select>';
				
				$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
								'September', 'October', 'November', 'December');
				
				$html .= '
				<select name="field_' . $this->id . '_month" id="field_' . $this->id . '_month">';
				$html .= '<option value=""' . $default_select . '>------</option>';
				
				for ( $i = 0; $i < 12; $i++ ) {
					if ( $month == $months[$i] ) {
						$selected = ' selected = "selected"';
					} else {
						$selected = '';
					}
					
					$html .= '<option value="' . $months[$i] . '"' . $selected . '>' . $months[$i] . '</option>';
				}

				$html .= '</select>';
				
				$html .= '
				<select name="field_' . $this->id . '_year" id="field_' . $this->id . '_year">';
				$html .= '<option value=""' . $default_select . '>----</option>';
								
				for ( $i = date( 'Y', time() ); $i > 1899; $i-- ) {
					if ( $year == $i ) {
						$selected = ' selected = "selected"'; 
					} else {
						$selected = '';
					}
				
					$html .= '<option value="' . $i .'"' . $selected . '>' . $i . '</option>';
				}
				
				$html .= '</select>';
				$html .= '<span class="signup-description">' . $this->desc . '</span>';
				$html .= '</div>';
				
			break;
		}
		
		return $html;
	}
	
	function get_field_data($user_id) {
		return new BP_XProfile_ProfileData($this->id, $user_id);
	}
	
	 function get_children($for_editing = false) {
		global $wpdb;
		
		//This is done here so we don't have problems with sql injection
		if ( $this->order_by == 'asc' && !$for_editing ) {
			$sort_sql = 'ORDER BY name ASC';
		} else if ( $this->order_by == 'desc' && !$for_editing ) {
			$sort_sql = 'ORDER BY name DESC';
		} else {
			$sort_sql = 'ORDER BY option_order ASC';
		}
		
		//This eliminates a problem with getting all fields when there is no id for the object
		if ( !$this->id ) {
			$parent_id = -1;
		} else {
			$parent_id = $this->id;
		}
		
		$sql = $wpdb->prepare( "SELECT * FROM $this->table_name_fields WHERE parent_id = %d AND group_id = %d $sort_sql", $parent_id, $this->group_id );

		if ( !$children = $wpdb->get_results($sql) )
			return false;
			
		return $children;
	} 
	
	function delete_children() {
		global $wpdb;

		$sql = $wpdb->prepare("DELETE FROM $this->table_name_fields	WHERE parent_id = %d", $this->id);

		$wpdb->query($sql);
	}
	
	function render_admin_form_children() {
		//This function populates the items for radio buttons checkboxes and drop down boxes
		$input_types = array( 'checkbox', 'selectbox', 'multiselectbox', 'radio' );	
		
		foreach ($input_types as $type) { 
			$default_name = '';
			
			if ( $type == 'multiselectbox' || $type == 'checkbox' ) {
				$default_input = 'checkbox';
			} else {
				$default_input = 'radio';
			}
		?>
			<div id="<?php echo $type ?>" class="options-box" style="<?php if ( $this->type != $type ) { ?>display: none;<?php } ?> margin-left: 15px;">
				<h4><?php _e('Please enter options for this Field:') ?></h4>
				<p>Order By: 
					
					<select name="sort_order_<?php echo $type ?>" id="sort_order_<?php echo $type ?>" >
						<option value="default" <?php if ( $this->order_by == 'default' ) {?> selected="selected"<?php } ?> >Order Entered</option>
						<option value="asc" <?php if ( $this->order_by == 'asc' ) {?> selected="selected"<?php } ?>>Name - Ascending</option>
						<option value="desc" <?php if ( $this->order_by == 'desc' ) {?> selected="selected"<?php } ?>>Name - Descending</option>
					</select>
	
				<?php
				$options = $this->get_children(true);
				
				if ( !empty($options) ) {
					for ( $i = 0; $i < count($options); $i++ ) { 
						$j = $i + 1;
						
						if ( $type == 'multiselectbox' || $type == 'checkbox' )
							$default_name = '[' . $j . ']';
					?>
						<p><?php _e('Option') ?> <?php echo $j ?>: 
						   <input type="text" name="<?php echo $type ?>_option[<?php echo $j ?>]" id="<?php echo $type ?>_option<?php echo $j ?>" value="<?php echo $options[$i]->name ?>" />
						   <input type="<?php echo $default_input ?>" name="isDefault_<?php echo $type ?>_option<?php echo $default_name; ?>" <?php if ( $options[$i]->sort_order == 'CHECKED' ) {?> checked="checked"<?php } ?> " /> Default Value 
						<a href="admin.php?page=xprofile_settings&amp;mode=delete_option&amp;option_id=<?php echo $options[$i]->id ?>" class="ajax-option-delete" id="delete-<?php echo $options[$i]->id ?>">[x]</a></p>
						</p>
					<?php } // end for ?>
					<input type="hidden" name="<?php echo $type ?>_option_number" id="<?php echo $type ?>_option_number" value="<?php echo $j ?>" />
				
				<?php 
				} else { 
					if ( $type == 'multiselectbox' || $type == 'checkbox' )
						$default_name = '[1]';
				?>
					
					<p><?php _e('Option') ?> 1: <input type="text" name="<?php echo $type ?>_option[1]" id="<?php echo $type ?>_option1" />
					<input type="<?php echo $default_input ?>" name="isDefault_<?php echo $type ?>_option<?php echo $default_name; ?>" id="isDefault_<?php echo $type ?>_option" <?php if ( $options[$i]->sort_order == 'CHECKED' ) {?> checked="checked"<?php } ?>" value="1" /> Default Value
					<input type="hidden" name="<?php echo $type ?>_option_number" id="<?php echo $type ?>_option_number" value="2" />
				
				<?php } // end if ?>
				<div id="<?php echo $type ?>_more"></div>					
				<p><a href="javascript:add_option('<?php echo $type ?>')"><?php _e('Add Another Option') ?></a></p>
			</div>

		<?php } 
	}
		
	function render_admin_form( $message = '' ) {
		if ( $this->id == null ) {
			$title = __('Add Field');
			$action = "admin.php?page=xprofile_settings&amp;group_id=" . $this->group_id . "&amp;mode=add_field";
		} else {
			$title = __('Edit Field');
			$action = "admin.php?page=xprofile_settings&amp;mode=edit_field&amp;group_id=" . $this->group_id . "&amp;field_id=" . $this->id;			
			$options = $this->get_children();
		}
	
		
	?>
	
	<div class="wrap">
		
		<h2><?php echo $title; ?></h2>
		<br />
		
		<?php
			if ( $message != '' ) {
		?>
			<div id="message" class="error fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>
		
		<form action="<?php echo $action ?>" method="post">
			<div id="poststuff">		
				<div id="titlediv">
					<h3><label for="title"><?php _e("Field Title") ?> *</label></h3>
					<div id="titlewrap">
						<input type="text" name="title" id="title" value="<?php echo $this->name ?>" style="width:50%" />
					</div>
				</div>
			
				<div id="titlediv">
					<h3><label for="description"><?php _e("Field Description") ?></label></h3>
					<div id="titlewrap">
						<textarea name="description" id="description" rows="8" cols="60" style="border: none; width: 99%;"><?php echo $this->desc ?></textarea>
					</div>
				</div>
	
				<div id="titlediv">
					<h3><label for="required"><?php _e("Is This Field Required?") ?> *</label></h3>
					<select name="required" id="required" style="width: 30%">
						<option value="0"<?php if ( $this->is_required == '0' ) { ?> selected="selected"<?php } ?>>Not Required</option>
						<option value="1"<?php if ( $this->is_required == '1' ) { ?> selected="selected"<?php } ?>>Required</option>
					</select>
				</div>

				<div id="titlediv">
					<h3><label for="public"><?php _e("Default Field Privacy Settings") ?> *</label></h3>
					<select name="public" id="public" style="width: 30%">
						<option value="1"<?php if ( $this->is_public== '1' ) { ?> selected="selected"<?php } ?>>Viewable by everyone</option>
						<option value="0"<?php if ( $this->is_public== '0' ) { ?> selected="selected"<?php } ?>>Viewable only by member</option>
					</select>
				</div>

				<div id="titlediv">
					<h3><label for="fieldtype"><?php _e("Field Type") ?> *</label></h3>
					<select name="fieldtype" id="fieldtype" onchange="show_options(this.value)" style="width: 30%">
						<option value="textbox"<?php if ( $this->type == 'textbox' ) {?> selected="selected"<?php } ?>>Text Box</option>
						<option value="textarea"<?php if ( $this->type == 'textarea' ) {?> selected="selected"<?php } ?>>Multi-line Text Box</option>
						<option value="datebox"<?php if ( $this->type == 'datebox' ) {?> selected="selected"<?php } ?>>Date Selector</option>
						<option value="radio"<?php if ( $this->type == 'radio' ) {?> selected="selected"<?php } ?>>Radio Buttons</option>
						<option value="selectbox"<?php if ( $this->type == 'selectbox' ) {?> selected="selected"<?php } ?>>Drop-down Select Box</option>
						<option value="multiselectbox"<?php if ( $this->type == 'multiselectbox' ) {?> selected="selected"<?php } ?>>Multi Select Box</option>
						<option value="checkbox"<?php if ( $this->type == 'checkbox' ) {?> selected="selected"<?php } ?>>Checkboxes</option>
					</select>
				</div>

				<?php $this->render_admin_form_children() ?>				
	
				<p class="submit">
						&nbsp;<input type="submit" value="<?php _e("Save") ?> &raquo;" name="saveField" id="saveField" style="font-weight: bold" />
						 <?php _e('or') ?> <a href="admin.php?page=xprofile_settings" style="color: red"><?php _e('Cancel') ?></a>
				</p>
			
			<div class="clear"></div>
			
			<?php if ( function_exists('wp_nonce_field') )
				wp_nonce_field('xprofile_delete_option');
			?>
			
		</form>
		
		<div class="clear">&nbsp;</div><br />
		
		<h2>Add Prebuilt Field</h2>
		<?php $this->render_prebuilt_fields(); ?>
		
	</div>
	
	<?php
	}
	
	/** Static Functions **/
	function render_prebuilt_fields() {
		$action = "admin.php?page=xprofile_settings&amp;group_id=" . $this->group_id . "&amp;mode=add_field";
		
		// Files in wp-content/themes directory and one subdir down
		$prebuilt_fields_path = ABSPATH . MUPLUGINDIR . '/bp-xprofile/prebuilt-fields';
		if( !empty( $prebuilt_fields_path ) ){
			$prebuilt_fields_dir = @opendir($prebuilt_fields_path);		
			if ( $prebuilt_fields_dir ){ 
				?><table class="form-table"><?php
				$counter = 0;
				while ( ($field_file = readdir( $prebuilt_fields_dir )) !== false ) {
				
					if ( $field_file{0} == '.' || $field_file == '..' || $field_file == 'CVS' || $field_file == '.svn' )
						continue;
					
					$field_file_path = $prebuilt_fields_path . '/' . $field_file;
									
					if ( is_readable( $field_file_path ) ) {
						$field_data  = $this->get_prebuilt_field_data( $field_file_path ); ?>
						<tr>
							<td style="vertical-align:top;">
								<h3>
									<?php echo $field_data['Name'] . $field_data['Version']; ?> by <a href="<?php echo $field_data['URI'];?>"> 
										<?php echo $field_data['Author'];?></a>
								</h3>
							</td>
							<td>
								<form action="<?php echo $action ?>" method="post">
									
									<label for="title">* <?php _e("Field Title") ?></label>
									<div>
										<input type="text" name="title" id="title" value="<?php echo $field_data['Name']; ?>" style="width:50%" />
									</div>
									<p></p>
									<label for="description"><?php _e("Field Description") ?></label>
									<div>
										<textarea name="description" id="description" rows="5" cols="60"><?php echo $field_data['Description']; ?></textarea>
									</div>
									<p></p>
									<label for="required">* <?php _e("Is This Field Required?") ?></label>
									<div>
										<select name="required" id="required">
											<option value="0"<?php if ( $this->is_required == '0' ) { ?> selected="selected"<?php } ?>>Not Required</option>
											<option value="1"<?php if ( $this->is_required == '1' ) { ?> selected="selected"<?php } ?>>Required</option>
										</select>
									</div>
									<p></p>
									<label for="fieldtype">* <?php _e("Field Type") ?></label>
									<div>
										<select name="fieldtype" id="fieldtype" onchange="show_options(this.value)">
											<?php if (in_array('textbox', $field_data['Types'])) { ?>
												<option value="textbox"<?php if ( $this->type == 'textbox' ) {?> selected="selected"<?php } ?>>Text Box</option>
											<?php } if (in_array('textarea', $field_data['Types'])) { ?>
												<option value="textarea"<?php if ( $this->type == 'textarea' ) {?> selected="selected"<?php } ?>>Multi-line Text Box</option>
											<?php } if (in_array('datebox', $field_data['Types'])) { ?>
												<option value="datebox"<?php if ( $this->type == 'datebox' ) {?> selected="selected"<?php } ?>>Date Selector</option>
											<?php } if (in_array('radio', $field_data['Types'])) { ?>
												<option value="radio"<?php if ( $this->type == 'radio' ) {?> selected="selected"<?php } ?>>Radio Buttons</option>
											<?php } if (in_array('selectbox', $field_data['Types'])) { ?>
												<option value="selectbox"<?php if ( $this->type == 'selectbox' ) {?> selected="selected"<?php } ?>>Drop-down Select Box</option>
											<?php } if (in_array('multiselectbox', $field_data['Types'])) { ?>
												<option value="multiselectbox"<?php if ( $this->type == 'multiselectbox' ) {?> selected="selected"<?php } ?>>Multi Select Box</option>
											<?php } ?>
										</select>
									</div>
									
									<p class="submit">									
								 	  <input type="submit" value="<?php _e("Add") ?> &raquo;" name="saveField" id="saveField<?php echo $counter;?>" class="button" />
									  <input type="hidden" name="field_file" value="<?php echo $field_file_path; ?>">
									</p>
							  </form>
							</td>
						</tr>
						<?php
					}
					$counter++;
				}
				?></table><?php
			} else {
				?><p>No prebuilt fields available at this time.</p><?php
			}
			@closedir( $prebuilt_fields_dir );
		}
	}
	
	function get_prebuilt_field_data( $field_file ) {
		$allowed_tags = array(
			'a' => array(
				'href' => array(),'title' => array()
				),
			'abbr' => array(
				'title' => array()
				),
			'acronym' => array(
				'title' => array()
				),
			'code' => array(),
			'em' => array(),
			'strong' => array()
		);
		
		$field_data = implode( '', file( $field_file ) );
		$field_data = str_replace ( '\r', '\n', $field_data );
		preg_match( '|Field Name:(.*)$|mi', $field_data, $field_name );
		preg_match( '|URI:(.*)$|mi', $field_data, $uri );
		preg_match( '|Description:(.*)$|mi', $field_data, $description );
		preg_match( '|Types:(.*)$|mi', $field_data, $types );

		if ( preg_match( '|Version:(.*)|i', $field_data, $version ) )
			$version = wp_kses( trim( $version[1] ), $allowed_tags );
		else
			$version = '';

		$name = wp_kses( trim( $field_name[1] ), $allowed_tags );
		$description = wptexturize( wp_kses( trim( $description[1] ), $allowed_tags ) );
		$types = split( ",", wptexturize( wp_kses( trim( $types[1] ), $allowed_tags ) ) );

		if ( preg_match( '|Author:(.*)$|mi', $field_data, $author_name ) ) {
			if ( empty( $author_uri ) ) {
				$author = wp_kses( trim( $author_name[1] ), $allowed_tags );
			} else {
				$author = sprintf( '<a href="%1$s" title="%2$s">%3$s</a>', $author_uri, __( 'Visit author homepage' ), 
					wp_kses( trim( $author_name[1] ), $allowed_tags ) );
			}
		} else {
			$author = __('Anonymous');
		}

		return array( 'Name' => $name, 'URI' => $uri, 'Description' => $description, 'Author' => $author, 'Version' => $version, 'Types' => $types);
	}
	
	function get_signup_fields() {
		global $wpdb, $bp_xprofile_table_name_fields, $bp_xprofile_table_name_groups;
		
		$sql = $wpdb->prepare("SELECT f.id FROM $bp_xprofile_table_name_fields AS f, $bp_xprofile_table_name_groups AS g WHERE g.name = 'Basic' AND f.parent_id = 0	AND g.id = f.group_id ORDER BY f.id");

		if ( !$temp_fields = $wpdb->get_results($sql) )
			return false;
		
		for ( $i = 0; $i < count($temp_fields); $i++ ) {
			$fields[] = new BP_XProfile_Field( $temp_fields[$i]->id, null, false );
		}
		
		return $fields;
	}

	function admin_validate() {
		global $message;
		
		// Validate Form
		if ( $_POST['title'] == '' || $_POST['required'] == '' || $_POST['fieldtype'] == '' ) {
			$message = __('Please make sure you fill out all required fields.');
			return false;
		} else if ( empty($_POST['field_file']) && $_POST['fieldtype'] == 'radio' && empty($_POST['radio_option'][1]) ) {
			$message = __('Radio button field types require at least one option. Please add options below.');	
			return false;
		} else if ( empty($_POST['field_file']) && $_POST['fieldtype'] == 'selectbox' && empty($_POST['selectbox_option'][1]) ) {
			$message = __('Select box field types require at least one option. Please add options below.');	
			return false;	
		} else if ( empty($_POST['field_file']) && $_POST['fieldtype'] == 'multiselectbox' && empty($_POST['multiselectbox_option'][1]) ) {
			$message = __('Select box field types require at least one option. Please add options below.');	
			return false;	
		} else if ( empty($_POST['field_file']) && $_POST['fieldtype'] == 'checkbox' && empty($_POST['checkbox_option'][1]) ) {
			$message = __('Checkbox field types require at least one option. Please add options below.');	
			return false;		
		} else {
			return true;
		}
	}
	
	function get_type( $field_id ) {
		global $wpdb, $bp_xprofile_table_name_fields;

		if ( $field_id ) {
			$sql = $wpdb->prepare("SELECT type FROM $bp_xprofile_table_name_fields WHERE id = %d", $field_id);

			if ( !$field_type = $wpdb->get_var($sql) )
				return false;
		
			return $field_type;
		}
		
		return false;
	}
	
	function delete_for_group( $group_id ) {
		global $wpdb, $bp_xprofile_table_name_fields;

		if ( $group_id ) {
			$sql = $wpdb->prepare("DELETE FROM $bp_xprofile_table_name_fields WHERE group_id = %d", $group_id);

			if ( $wpdb->get_var($sql) === false ) {
				return false;
			}
			
			return true;
		}
		
		return false;
	}
}


Class BP_XProfile_ProfileData {
	var $id;
	var $user_id;
	var $field_id;
	var $value;
	var $last_updated;
	var $table_name_data;
	var $table_name_fields;
		
	function bp_xprofile_profiledata( $field_id = null, $user_id = null ) {
		global $bp_xprofile_table_name_data, $bp_xprofile_table_name_fields;

		$this->table_name_data = $bp_xprofile_table_name_data;
		$this->table_name_fields = $bp_xprofile_table_name_fields;
		
		if ( $field_id ) {
			$this->populate( $field_id, $user_id );
		}
	}

	function populate( $field_id, $user_id )  {
		global $wpdb, $userdata;
		
		if ( is_null($user_id) )
			$user_id = $userdata->ID;
		
		$sql = $wpdb->prepare("SELECT * FROM $this->table_name_data	WHERE field_id = %d AND user_id = %d", $field_id, $user_id);

		if ( $profiledata = $wpdb->get_row($sql) ) {
			$this->id = $profiledata->id;
			$this->user_id = $profiledata->user_id;
			$this->field_id = $profiledata->field_id;
			$this->value = $profiledata->value;
			$this->last_updated = $profiledata->last_updated;
		}
	}
	
	function exists() {
		global $wpdb, $userdata;
		
		// check to see if there is data already for the user.
		$sql = $wpdb->prepare("SELECT id FROM $this->table_name_data WHERE user_id = %d AND field_id = %d", $userdata->ID, $this->field_id);

		if ( !$wpdb->get_row($sql) ) 
			return false;

		return true;		
	}
		
	function is_valid_field() {
		global $wpdb;
		
		// check to see if this data is actually for a valid field.
		$sql = $wpdb->prepare("SELECT id FROM $this->table_name_fields WHERE id = %d", $this->field_id);

		if ( !$wpdb->get_row($sql) ) 
			return false;
		
		return true;
	}

	function save() {
		global $wpdb, $userdata;
		
		$this->last_updated = date( 'Y-m-d H:i:s' );

		if ( $this->is_valid_field() ) {
			if ( $this->exists() && $this->value != '' ) {
				$sql = $wpdb->prepare("UPDATE $this->table_name_data SET value = %s, last_updated = %s WHERE user_id = %d AND field_id = %d", $this->value, $this->last_updated, $this->user_id, $this->field_id);
			} else if ( $this->exists() and $this->value == '' ) {
				// Data removed, delete the entry.
				$this->delete();
			} else {
				$sql = $wpdb->prepare("INSERT INTO $this->table_name_data (user_id, field_id, value, last_updated) VALUES (%d, %d, %s, %s)", $this->user_id, $this->field_id, $this->value, $this->last_updated);
			}
						
			if ( $wpdb->query($sql) === false )
				return false;
			
			// Updated last site activity for this user.
			update_usermeta( $userdata->ID, 'last_activity', $this->last_updated ); 
			
			return true;
		} else {
			return false;
		}
	}

	function delete() {
		global $wpdb;
		
		$sql = $wpdb->prepare("DELETE FROM $this->table_name_data WHERE field_id = %d AND user_id = %d", $this->field_id, $this->user_id);

		if ( $wpdb->query($sql) === false )
			return false;
		
		return true;
	}
	
	/** Static Functions **/
	
	function get_value_byid( $field_id, $user_id = null ) {
		global $wpdb, $current_userid, $bp_xprofile_table_name_data;

		if ( !$user_id )
			$user_id = $current_userid;

		$sql = $wpdb->prepare("SELECT * FROM $bp_xprofile_table_name_data WHERE field_id = %d AND user_id = %d", $field_id, $user_id );

		if ( $profileData = $wpdb->get_row($sql) ) {
			return $profileData->value;
		} else {
			return false;
		}
	}
	
	function get_value_byfieldname( $fields, $user_id = null ) {
		global $current_userid, $wpdb, $bp_xprofile_table_name_fields, $bp_xprofile_table_name_data;

		if ( !$fields )
			return false;

		if ( !$user_id )
			$user_id = $current_userid;

		if ( is_array($fields) ) {
			for ( $i = 0; $i < count($fields); $i++ ) {
				if ( $i == 0 )
					$field_sql .= $wpdb->prepare( "AND ( f.name = %s ", $fields[$i] );
				else 
					$field_sql .= $wpdb->prepare( "OR f.name = %s ", $fields[$i] );
			}
			
			$field_sql .= ')';
		} else {
			$field_sql .= $wpdb->prepare( "AND f.name = %s", $fields );
		}

		$sql = $wpdb->prepare( "SELECT d.value, f.name FROM $bp_xprofile_table_name_data d, $bp_xprofile_table_name_fields f WHERE d.field_id = f.id AND d.user_id = %d AND f.parent_id = 0 $field_sql", $user_id );
	
		if ( !$values = $wpdb->get_results($sql) )
			return false;
		
		$new_values = array();
		
		if ( is_array($fields) ) {
			for ( $i = 0; $i < count($values); $i++ ) {
				for ( $j = 0; $j < count($fields); $j++ ) {
					if ( $values[$i]->name == $fields[$j] ) {
						$new_values[$fields[$j]] = $values[$i]->value;
					} else if ( !array_key_exists( $fields[$j], $new_values ) ) {
						$new_values[$fields[$j]] = NULL;
					}
				}
			}
		} else {
			$new_values = $values[0]->value;
		}
		
		return $new_values;
	}
	
	function delete_for_field( $field_id ) {
		global $wpdb, $userdata, $bp_xprofile_table_name_data;

		$sql = $wpdb->prepare("DELETE FROM $bp_xprofile_table_name_data WHERE field_id = %d", $field_id);

		if ( $wpdb->query($sql) === false )
			return false;
		
		return true;
	}
	
	function get_last_updated( $user_id ) {
		global $wpdb, $bp_xprofile_table_name_data;
		
		$last_updated = $wpdb->get_var( $wpdb->prepare( "SELECT last_updated FROM $bp_xprofile_table_name_data WHERE user_id = %d ORDER BY last_updated LIMIT 1", $user_id ) );
		
		return $last_updated;
	}
	
}


?>

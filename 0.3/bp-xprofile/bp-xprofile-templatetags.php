<?php

Class BP_XProfile_Template {
	var $current_group = -1;
	var $group_count;
	var $groups;
	var $group;
	
	var $current_field = -1;
	var $field_count;
	var $field_has_data;
	var $field;
	var $is_public;
	
	var $in_the_loop;
	var $user_id;

	function bp_xprofile_template($user_id) {
		$this->groups = BP_XProfile_Group::get_all(true);
		$this->group_count = count($this->groups);
		$this->user_id = $user_id;
	}
	
	function has_groups() {
		if ( $this->group_count )
			return true;
		
		return false;
	}
	
	function next_group() {
		$this->current_group++;

		$this->group = $this->groups[$this->current_group];
		$this->field_count = count($this->group->fields);
		
		for ( $i = 0; $i < $this->field_count; $i++ ) {
			$this->group->fields[$i] = new BP_XProfile_Field( $this->group->fields[$i]->id, $this->user_id );	
		}
		
		return $this->group;
	}
	
	function rewind_groups() {
		$this->current_group = -1;
		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}
	}
	
	function profile_groups() { 
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 == $this->group_count ) {
			do_action('loop_end');
			// Do some cleaning up after the loop
			$this->rewind_groups();
		}

		$this->in_the_loop = false;
		return false;
	}
	
	function the_profile_group() {
		global $group;

		$this->in_the_loop = true;
		$group = $this->next_group();

		if ( $this->current_group == 0 ) // loop has just started
			do_action('loop_start');
	}
	
	/**** FIELDS ****/
	
	function next_field() {
		$this->current_field++;

		$this->field = $this->group->fields[$this->current_field];
		return $this->field;
	}
	
	function rewind_fields() {
		$this->current_field = -1;
		if ( $this->field_count > 0 ) {
			$this->field = $this->group->fields[0];
		}
	}	
	
	function has_fields() { 
		$has_data = false;

		if ( count($this->group->fields) > 0 ) {
			for ( $i = 0; $i < count($this->group->fields); $i++ ) { 
				$field = $this->group->fields[$i];

				if ( $field->data->value != null ) {
					$has_data = true;
				}
			}
		}

		if($has_data)
			return true;
		
		return false;
	}
	
	function profile_fields() {
		if ( $this->current_field + 1 < $this->field_count ) {
			return true;
		} elseif ( $this->current_field + 1 == $this->field_count ) {
			// Do some cleaning up after the loop
			$this->rewind_fields();
		}

		return false;	
	}
	
	function the_profile_field() {
		global $field;

		$field = $this->next_field();
		$this->is_public = $field->is_public;	
		if ( $field->data->value != '' ) {
			$this->field_has_data = true;
		}
		else {
			$this->field_has_data = false;
		}
	}
}

function has_profile() { 
	global $profile_template;
	return $profile_template->has_groups();
}

function profile_groups() { 
	global $profile_template;
	return $profile_template->profile_groups();
}

function the_profile_group() {
	global $profile_template;
	return $profile_template->the_profile_group();
}

function group_has_fields() {
	global $profile_template;
	return $profile_template->has_fields();
}

function field_has_data() {
	global $profile_template;
	return $profile_template->field_has_data;
}
function field_has_public_data() {
	global $profile_template;
	if ($profile_template->field_has_data && $profile_template->is_public== 1) {
		return true;
	}
	return false;
}

function the_profile_group_name() {
	global $group;
	echo $group->name;
}

function the_profile_group_description() {
	global $group;
	echo $group->description;
}

function profile_fields() {
	global $profile_template;
	return $profile_template->profile_fields();
}

function the_profile_field() {
	global $profile_template;
	return $profile_template->the_profile_field();
}

function the_profile_field_name() {
	global $field;
	echo $field->name;
}

function the_profile_field_value() {
	global $field;
	
	if ( bp_is_serialized($field->data->value) ) {
		$field_value = unserialize($field->data->value);
		$field_value = implode( ", ", $field_value );
		$field->data->value = $field_value;
	}
	
	if ( $field->type == "datebox" ) {
		$field->data->value = bp_format_time( $field->data->value, true );
	}
	
	echo $field->data->value;
}

function the_profile_picture() {
	global $coreuser_id;
	echo xprofile_get_avatar($coreuser_id, 2);
}

function the_profile_picture_thumbnail() {
	global $coreuser_id;
	echo xprofile_get_avatar($coreuser_id, 1);
}



?>

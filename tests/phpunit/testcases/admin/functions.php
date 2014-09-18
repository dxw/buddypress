<?php
/**
 * @group admin
 */
class BP_Tests_Admin_Functions extends BP_UnitTestCase {
	protected $old_current_user = 0;

	public function setUp() {
		parent::setUp();
		$this->old_current_user = get_current_user_id();
		$this->set_current_user( $this->create_user( array( 'role' => 'administrator' ) ) );

		if ( ! function_exists( 'bp_admin' ) ) {
			require_once( BP_PLUGIN_DIR . 'bp-core/bp-core-admin.php' );
		}

		if ( ! function_exists( 'bp_new_site' ) ) {
			bp_admin();
		}
	}

	public function tearDown() {
		parent::tearDown();
		$this->set_current_user( $this->old_current_user );
	}

	public function test_bp_admin_list_table_current_bulk_action() {
		$_REQUEST['action'] = 'foo';
		$_REQUEST['action2'] = '-1';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );

		$_REQUEST['action'] = '-1';
		$_REQUEST['action2'] = 'foo';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );

		$_REQUEST['action'] = 'bar';
		$_REQUEST['action2'] = 'foo';
		$this->assertEquals( bp_admin_list_table_current_bulk_action(), 'foo' );
	}

	public function test_bp_core_admin_get_active_components_from_submitted_settings() {
		$get_action = isset( $_GET['action'] ) ? $_GET['action'] : null;
		$ac = buddypress()->active_components;

		// Standard deactivation from All screen
		unset( $_GET['action'] );
		buddypress()->active_components = array(
			'activity' => 1,
			'friends' => 1,
			'groups' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted = array(
			'groups' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted ), array( 'groups' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Activating deactivated components from the Inactive screen
		$_GET['action'] = 'inactive';
		buddypress()->active_components = array(
			'activity' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted2 = array(
			'groups' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted2 ), array( 'activity' => 1, 'groups' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Activating from the Retired screen
		$_GET['action'] = 'retired';
		buddypress()->active_components = array(
			'activity' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted3 = array(
			'forums' => 1,
		);

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted3 ), array( 'activity' => 1, 'forums' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// Deactivating from the Retired screen
		$_GET['action'] = 'retired';
		buddypress()->active_components = array(
			'activity' => 1,
			'forums' => 1,
			'members' => 1,
			'messages' => 1,
			'settings' => 1,
			'xprofile' => 1,
		);

		$submitted4 = array();

		$this->assertEquals( bp_core_admin_get_active_components_from_submitted_settings( $submitted4 ), array( 'activity' => 1, 'members' => 1, 'messages' => 1, 'settings' => 1, 'xprofile' => 1 ) );

		// reset
		if ( $get_action ) {
			$_GET['action'] = $get_action;
		} else {
			unset( $_GET['action'] );
		}

		buddypress()->active_components = $ac;
	}
}

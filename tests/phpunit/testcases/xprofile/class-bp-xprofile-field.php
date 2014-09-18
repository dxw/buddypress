<?php
/**
 * @group xprofile
 * @group BP_XProfile_Field
 */
class BP_Tests_BP_XProfile_Field_TestCases extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_can_delete_save() {
		$group = $this->factory->xprofile_group->create();
		$field = $this->factory->xprofile_field->create( array(
			'field_group_id' => $group,
			'type' => 'textbox',
		) );

		$f = new BP_XProfile_Field( $field );
		$f->can_delete = 0;
		$f->save();

		$f2 = new BP_XProfile_Field( $field );
		$this->assertEquals( '0', $f2->can_delete );
	}
}

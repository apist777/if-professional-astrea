<?php
/**
 * Tests for ASTREA Core's Professional Profile feature (Construction Order 003).
 *
 * Core-only integration tests (WP_UnitTestCase, real WordPress post/postmeta
 * APIs). Theme display (Query Loop + core/post-meta binding rendering) and
 * the Core-inactive/deactivate/reactivate states are covered by
 * tools/ci/smoke-test.sh against a real running site, consistent with how
 * OfficeProfileTest.php split responsibilities in Construction Order 002.
 *
 * @package Astrea\Core
 */

use Astrea\Core\ProfessionalProfile;
use Astrea\Core\ProfessionalProfile\Admin as ProfessionalProfileAdmin;

/**
 * @covers \Astrea\Core\ProfessionalProfile
 */
class ProfessionalProfileTest extends WP_UnitTestCase {

	public function tear_down() {
		$_POST = array();
		parent::tear_down();
	}

	private function create_professional( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => ProfessionalProfile\POST_TYPE,
					'post_status' => 'publish',
					'post_title'  => 'テスト専門家',
				),
				$args
			)
		);
	}

	public function test_zero_profiles_returns_empty_array() {
		$this->assertSame( array(), ProfessionalProfile\get_profiles() );
	}

	public function test_one_profile_is_returned() {
		$id = $this->create_professional( array( 'post_title' => '佐藤 花子' ) );

		$profiles = ProfessionalProfile\get_profiles();

		$this->assertCount( 1, $profiles );
		$this->assertSame( '佐藤 花子', $profiles[0]['name'] );
	}

	public function test_multiple_profiles_are_all_returned() {
		$this->create_professional( array( 'post_title' => 'A' ) );
		$this->create_professional( array( 'post_title' => 'B' ) );
		$this->create_professional( array( 'post_title' => 'C' ) );

		$this->assertCount( 3, ProfessionalProfile\get_profiles() );
	}

	public function test_display_order_uses_menu_order_then_title_then_id() {
		$c = $this->create_professional(
			array(
				'post_title' => 'Charlie',
				'menu_order' => 1,
			)
		);
		$a = $this->create_professional(
			array(
				'post_title' => 'Alpha',
				'menu_order' => 0,
			)
		);
		$b = $this->create_professional(
			array(
				'post_title' => 'Bravo',
				'menu_order' => 0,
			)
		);

		$ids = wp_list_pluck( ProfessionalProfile\get_profiles(), 'id' );

		// menu_order 0 (Alpha, Bravo — tie broken by title) before menu_order 1 (Charlie).
		$this->assertSame( array( $a, $b, $c ), $ids );
	}

	public function test_editing_a_profile_is_reflected_in_get_profile() {
		$id = $this->create_professional( array( 'post_title' => '編集前' ) );

		wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => '編集後',
			)
		);

		$this->assertSame( '編集後', ProfessionalProfile\get_profile( $id )['name'] );
	}

	public function test_deleting_a_profile_removes_it_from_list_and_single_lookup() {
		$id = $this->create_professional();

		wp_delete_post( $id, true );

		$this->assertSame( array(), ProfessionalProfile\get_profiles() );
		$this->assertNull( ProfessionalProfile\get_profile( $id ) );
	}

	public function test_get_profile_rejects_nonexistent_id() {
		$this->assertNull( ProfessionalProfile\get_profile( 999999 ) );
	}

	public function test_get_profile_rejects_wrong_post_type() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( ProfessionalProfile\get_profile( $page_id ), 'Must not return data for a post of a different post type.' );
	}

	public function test_get_profiles_excludes_drafts() {
		$this->create_professional( array( 'post_status' => 'draft' ) );

		$this->assertSame( array(), ProfessionalProfile\get_profiles() );
	}

	public function test_photo_present_returns_attachment_id() {
		$id            = $this->create_professional();
		$attachment_id = self::factory()->attachment->create_object(
			array(
				'file'      => 'test-photo.png',
				'post_type' => 'attachment',
			)
		);
		// Not using set_post_thumbnail(): it additionally requires
		// wp_get_attachment_image() to resolve a real generated image,
		// which a fileless test-factory attachment cannot satisfy. What
		// this test actually verifies is the *read* side (get_profile()
		// surfacing whatever `_thumbnail_id` is set) — the real end-to-end
		// upload -> set-as-featured-image path was verified manually
		// against a real file over wp-env (see the Construction Order 003
		// report).
		update_post_meta( $id, '_thumbnail_id', $attachment_id );

		$this->assertSame( $attachment_id, ProfessionalProfile\get_profile( $id )['photo_id'] );
	}

	public function test_photo_absent_returns_null() {
		$id = $this->create_professional();

		$this->assertNull( ProfessionalProfile\get_profile( $id )['photo_id'] );
	}

	public function test_broken_attachment_reference_does_not_fatal_and_returns_null() {
		$id = $this->create_professional();
		update_post_meta( $id, '_thumbnail_id', 999999 );

		$this->assertNull( ProfessionalProfile\get_profile( $id )['photo_id'] );
	}

	public function test_all_meta_sanitizers_strip_tags() {
		$id = $this->create_professional();

		foreach ( ProfessionalProfile\meta_sanitizers() as $meta_key => $sanitize_callback ) {
			$sanitized = call_user_func( $sanitize_callback, '<script>alert(1)</script>安全なテキスト' );
			update_post_meta( $id, $meta_key, $sanitized );
		}

		$profile = ProfessionalProfile\get_profile( $id );

		$this->assertStringNotContainsString( '<script>', $profile['qualification'] );
		$this->assertStringContainsString( '安全なテキスト', $profile['qualification'] );
	}

	public function test_save_meta_writes_known_fields_for_capable_user() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ]                       = wp_create_nonce( ProfessionalProfileAdmin\NONCE_ACTION );
		$_POST[ ProfessionalProfile\META_QUALIFICATION ]                     = '行政書士';
		$_POST[ ProfessionalProfile\META_CAREER ]                            = '10年の実務経験';

		ProfessionalProfileAdmin\save_meta( $id );

		$profile = ProfessionalProfile\get_profile( $id );
		$this->assertSame( '行政書士', $profile['qualification'] );
		$this->assertSame( '10年の実務経験', $profile['career'] );
	}

	public function test_save_meta_ignores_unknown_meta_keys() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ] = wp_create_nonce( ProfessionalProfileAdmin\NONCE_ACTION );
		$_POST['some_unrelated_meta_key']              = 'should not be written';

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, 'some_unrelated_meta_key', true ) );
	}

	public function test_save_meta_rejects_missing_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfile\META_QUALIFICATION ] = '行政書士';
		// No nonce field set at all.

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, ProfessionalProfile\META_QUALIFICATION, true ) );
	}

	public function test_save_meta_rejects_invalid_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ]   = 'not-a-real-nonce';
		$_POST[ ProfessionalProfile\META_QUALIFICATION ] = '行政書士';

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, ProfessionalProfile\META_QUALIFICATION, true ) );
	}

	public function test_save_meta_rejects_non_capable_user() {
		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ]   = wp_create_nonce( ProfessionalProfileAdmin\NONCE_ACTION );
		$_POST[ ProfessionalProfile\META_QUALIFICATION ] = '行政書士';

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertSame( '', get_post_meta( $id, ProfessionalProfile\META_QUALIFICATION, true ), 'A user without edit_post capability must not be able to write Professional Profile meta.' );
	}

	public function test_deactivate_does_not_delete_professional_profiles() {
		$id = $this->create_professional( array( 'post_title' => '削除されないはずの専門家' ) );

		\Astrea\Core\deactivate();

		$this->assertNotNull( get_post( $id ), 'Decision 019: deactivation must never delete Core-owned data.' );
		$this->assertSame( '削除されないはずの専門家', get_post( $id )->post_title );
	}

	// -- Decision 023: representative flag -----------------------------------

	public function test_no_representative_by_default() {
		$this->create_professional();

		$this->assertSame( array(), ProfessionalProfile\get_representatives() );
	}

	public function test_one_representative_is_returned() {
		$id = $this->create_professional( array( 'post_title' => '代表 太郎' ) );
		update_post_meta( $id, ProfessionalProfile\META_IS_REPRESENTATIVE, true );

		$reps = ProfessionalProfile\get_representatives();

		$this->assertCount( 1, $reps );
		$this->assertSame( '代表 太郎', $reps[0]['name'] );
		$this->assertTrue( $reps[0]['is_representative'] );
	}

	public function test_multiple_representatives_are_allowed() {
		// Decision 023 leaves this an open question (see the Construction
		// Order 003A report) — no uniqueness constraint is enforced.
		$a = $this->create_professional( array( 'post_title' => '代表社員A' ) );
		$b = $this->create_professional( array( 'post_title' => '代表社員B' ) );
		update_post_meta( $a, ProfessionalProfile\META_IS_REPRESENTATIVE, true );
		update_post_meta( $b, ProfessionalProfile\META_IS_REPRESENTATIVE, true );

		$this->assertCount( 2, ProfessionalProfile\get_representatives() );
	}

	public function test_non_representative_profiles_are_not_flagged() {
		$id = $this->create_professional();

		$this->assertFalse( ProfessionalProfile\get_profile( $id )['is_representative'] );
	}

	public function test_save_meta_sets_is_representative_when_checkbox_present() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ]           = wp_create_nonce( ProfessionalProfileAdmin\NONCE_ACTION );
		$_POST[ ProfessionalProfile\META_IS_REPRESENTATIVE ]     = '1';

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertTrue( ProfessionalProfile\get_profile( $id )['is_representative'] );
	}

	public function test_save_meta_unsets_is_representative_when_checkbox_absent() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();
		update_post_meta( $id, ProfessionalProfile\META_IS_REPRESENTATIVE, true );

		// Real unchecked checkboxes are simply absent from $_POST.
		$_POST[ ProfessionalProfileAdmin\NONCE_FIELD ] = wp_create_nonce( ProfessionalProfileAdmin\NONCE_ACTION );

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertFalse( ProfessionalProfile\get_profile( $id )['is_representative'] );
	}

	public function test_save_meta_rejects_is_representative_change_without_nonce() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$id = $this->create_professional();

		$_POST[ ProfessionalProfile\META_IS_REPRESENTATIVE ] = '1';
		// No nonce field set.

		ProfessionalProfileAdmin\save_meta( $id );

		$this->assertFalse( ProfessionalProfile\get_profile( $id )['is_representative'] );
	}
}

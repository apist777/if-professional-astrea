<?php
/**
 * Tests for ASTREA Core's single Breadcrumb hierarchy resolver
 * (Construction Order 006, Decision 010/026).
 *
 * @package Astrea\Core
 */

use Astrea\Core\Seo;
use Astrea\Core\Service;
use Astrea\Core\ProfessionalProfile;
use Astrea\Core\Faq;

/**
 * @covers \Astrea\Core\Seo
 */
class BreadcrumbTest extends WP_UnitTestCase {

	public function test_front_page_has_no_breadcrumb() {
		update_option( 'show_on_front', 'posts' );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( array(), Seo\get_breadcrumb_items() );
	}

	public function test_service_archive_breadcrumb() {
		$this->go_to( get_post_type_archive_link( Service\POST_TYPE ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertCount( 2, $items );
		$this->assertNull( $items[1]['url'] );
	}

	public function test_service_single_breadcrumb_includes_archive_link() {
		$service_id = self::factory()->post->create(
			array(
				'post_type'  => Service\POST_TYPE,
				'post_title' => '契約書レビュー',
			)
		);
		$this->go_to( get_permalink( $service_id ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertCount( 3, $items );
		$this->assertNotNull( $items[1]['url'] ); // Archive is linked.
		$this->assertNull( $items[2]['url'] ); // Current page is not linked.
		$this->assertSame( '契約書レビュー', $items[2]['label'] );
	}

	public function test_professional_archive_breadcrumb() {
		$this->go_to( get_post_type_archive_link( ProfessionalProfile\POST_TYPE ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertCount( 2, $items );
	}

	public function test_faq_taxonomy_breadcrumb_includes_faq_archive_and_term() {
		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Faq\TAXONOMY,
				'name'     => '料金について',
			)
		);
		$this->go_to( get_term_link( $term ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertCount( 3, $items );
		$this->assertSame( '料金について', $items[2]['label'] );
		$this->assertNull( $items[2]['url'] );
	}

	public function test_page_with_ancestor_breadcrumb() {
		$parent_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => '親ページ',
			)
		);
		$child_id  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_title'  => '子ページ',
				'post_parent' => $parent_id,
			)
		);
		$this->go_to( get_permalink( $child_id ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertCount( 3, $items );
		$this->assertSame( '親ページ', $items[1]['label'] );
		$this->assertSame( '子ページ', $items[2]['label'] );
		$this->assertNull( $items[2]['url'] );
	}

	public function test_last_item_is_never_linked() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $page_id ) );

		$items = Seo\get_breadcrumb_items();

		$this->assertNull( end( $items )['url'] );
	}

	public function test_visual_breadcrumb_is_empty_on_front_page() {
		update_option( 'show_on_front', 'posts' );
		$this->go_to( home_url( '/' ) );

		$this->assertSame( '', Seo\render_breadcrumb_block() );
	}

	public function test_visual_breadcrumb_marks_current_page_with_aria_current() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'テストページ' ) );
		$this->go_to( get_permalink( $page_id ) );

		$html = Seo\render_breadcrumb_block();

		$this->assertStringContainsString( 'aria-current="page"', $html );
		$this->assertStringContainsString( 'aria-label=', $html );
		$this->assertStringStartsWith( '<nav', $html );
	}

	public function test_visual_breadcrumb_escapes_malicious_labels() {
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => '<script>alert(1)</script>',
			)
		);
		$this->go_to( get_permalink( $page_id ) );

		$html = Seo\render_breadcrumb_block();

		$this->assertStringNotContainsString( '<script>', $html );
	}
}

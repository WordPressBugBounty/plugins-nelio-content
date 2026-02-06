<?php
/**
 * Functions for adding custom actions and bulk edit options in post list table.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.8
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class customizes the post list screen.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.8
 */
class Nelio_Content_Post_List_Page {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_social_assets' ) );

		add_filter( 'manage_pages_columns', array( $this, 'add_page_column_for_auto_share' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'add_value_in_column_for_auto_share' ), 10, 2 );

		add_filter( 'manage_posts_columns', array( $this, 'add_post_column_for_auto_share' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'add_value_in_column_for_auto_share' ), 10, 2 );

		add_filter( 'post_class', array( $this, 'add_class_with_auto_share_info' ), 10, 3 );
		add_action( 'bulk_edit_custom_box', array( $this, 'maybe_add_quick_or_bulk_edit_for_auto_share' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'maybe_add_quick_or_bulk_edit_for_auto_share' ), 10, 2 );
		add_action( 'save_post', array( $this, 'update_auto_share_on_quick_or_bulk_edit' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'customize_row_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'customize_row_actions' ), 10, 2 );
		add_filter( 'display_post_states', array( $this, 'display_post_custom_states' ), 10, 2 );
	}

	/**
	 * Callback to enqueue social assets.
	 *
	 * @return void
	 */
	public function maybe_enqueue_social_assets() {

		if ( ! $this->is_current_screen_social_post_list() ) {
			return;
		}

		$custom_css  = '';
		$custom_css .= '.column-nc_auto_share { width: 10% !important; }';
		$custom_css .= '.nc-auto-share { color:grey; }';
		$custom_css .= '.nc-auto-share--is-enabled { color:green; font-weight:bold; }';
		$custom_css .= 'input[name="nc_auto_share"] + label + div.nc-auto-share-end { display: none; }';
		$custom_css .= 'input[name="nc_auto_share"]:checked + label + div.nc-auto-share-end { display: block; }';
		$custom_css .= 'label[for=nc_auto_share] { display: inline !important; }';
		wp_add_inline_style( 'list-tables', $custom_css );

		wp_add_inline_script(
			'inline-edit-post',
			'jQuery && jQuery(document).ready( function($) {' .
			'  ied = inlineEditPost.edit;' .
			'  inlineEditPost.edit = function(pid) { ' .
			'    ied.apply( this, arguments );' .
			'    if ( "object" === typeof pid ) pid = this.getId( pid );' .
			'    $field = $("input[name=nc_auto_share]");' .
			'    checked = $("#post-"+pid).hasClass("nc-is-auto-shared");' .
			'    $field.prop( "checked", checked );' .
			'    ' .
			'    post = document.getElementById("post-"+pid);' .
			'    re = /^.*(nc-auto-share-end--is-([^ ]+)).*$/;' .
			'    val = re.test( post.className ) ? post.className.replace( re, "$2" ) : "default";' .
			'    select = document.querySelector("div.nc-auto-share-end select");' .
			'    select.value = val;' .
			'  };' .
			'} );'
		);

		wp_enqueue_style(
			'nelio-content-post-list-page',
			nelio_content()->plugin_url . '/assets/dist/css/post-list-page.css',
			array( 'nelio-content-components' ),
			nelio_content_get_script_version( 'post-list-page' )
		);

		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-post-list-page', 'post-list-page', true );

		/** @var string $post_type */
		global $post_type;
		wp_add_inline_script(
			'nelio-content-post-list-page',
			sprintf(
				'NelioContent.initPage( %s );',
				wp_json_encode(
					array(
						'customStatuses' => array_values(
							array_filter(
								$this->get_post_custom_statuses( $post_type ),
								fn ( $status ) => ! empty( $status['available'] )
							)
						),
					)
				)
			)
		);
	}

	/**
	 * Callback to add auto share column on pages.
	 *
	 * @param array<string,string> $columns Columns.
	 *
	 * @return array<string,string>
	 */
	public function add_page_column_for_auto_share( $columns ) {
		return $this->add_post_column_for_auto_share( $columns, 'page' );
	}

	/**
	 * Callback to add auto share column.
	 *
	 * @param array<string,string> $columns   Columns.
	 * @param string               $post_type Post type.
	 *
	 * @return array<string,string>
	 */
	public function add_post_column_for_auto_share( $columns, $post_type ) {
		$post_types = nelio_content_get_post_types( 'social' );
		if ( ! in_array( $post_type, $post_types, true ) ) {
			return $columns;
		}

		$columns['nc_auto_share'] = _x( 'Auto Share', 'text', 'nelio-content' );
		return $columns;
	}

	/**
	 * Callback to add value in auto share column.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function add_value_in_column_for_auto_share( $column, $post_id ) {

		if ( 'nc_auto_share' !== $column ) {
			return;
		}

		$aux = Nelio_Content_Post_Helper::instance();
		if ( ! $aux->is_auto_share_enabled( $post_id ) ) {
			printf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( 'nc-auto-share nc-auto-share--is-disabled' ),
				esc_html_x( 'Disabled', 'text (auto share)', 'nelio-content' )
			);
			return;
		}

		$end_date = $aux->get_auto_share_end_date( $post_id );
		$cur_date = gmdate( 'Y-m-d' );

		if ( 'never' === $end_date ) {
			printf(
				wp_kses(
					/* translators: %s: Classname. */
					_x( '<span class="%s">Enabled</span><br>forever', 'text (auto share)', 'nelio-content' ),
					array(
						'span' => array( 'class' => true ),
						'br'   => array(),
					)
				),
				esc_attr( 'nc-auto-share nc-auto-share--is-enabled' )
			);
		} elseif ( 'unknown' === $end_date ) {
			printf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( 'nc-auto-share nc-auto-share--is-enabled' ),
				esc_html_x( 'Enabled', 'text (auto share)', 'nelio-content' )
			);
		} elseif ( $cur_date <= $end_date ) {
			printf(
				wp_kses(
					/* translators: %1$s: Classname. %2$s: Date. */
					_x( '<span class="%1$s">Enabled</span><br>until %2$s', 'text (auto share)', 'nelio-content' ),
					array(
						'span' => array( 'class' => true ),
						'br'   => array(),
					)
				),
				esc_attr( 'nc-auto-share nc-auto-share--is-enabled' ),
				esc_html( $end_date )
			);
		} else {
			printf(
				'<span class="%1$s">%2$s</span>',
				esc_attr( 'nc-auto-share nc-auto-share--is-finished' ),
				sprintf(
					wp_kses(
						/* translators: %s: Date. */
						_x( 'Finished<br>on %s', 'text (auto share)', 'nelio-content' ),
						array( 'br' => array() )
					),
					esc_html( $end_date )
				)
			);
		}
	}

	/**
	 * Callback to add class with auto share info.
	 *
	 * @param list<string> $classes Classes.
	 * @param list<string> $css_class CSS class.
	 * @param int          $post_id Post ID.
	 *
	 * @return list<string>
	 */
	public function add_class_with_auto_share_info( $classes, $css_class, $post_id ) {

		if ( ! is_admin() ) {
			return $classes;
		}

		$aux = Nelio_Content_Post_Helper::instance();
		if ( $aux->is_auto_share_enabled( $post_id ) ) {
			$end_mode = $aux->get_auto_share_end_mode( $post_id );
			array_push( $classes, 'nc-is-auto-shared', "nc-auto-share-end--is-{$end_mode}" );
		}

		return $classes;
	}

	/**
	 * Callback to add auto share settings.
	 *
	 * @param string $column    Column name.
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	public function maybe_add_quick_or_bulk_edit_for_auto_share( $column, $post_type ) {

		if ( 'nc_auto_share' !== $column ) {
			return;
		}

		$post_types = nelio_content_get_post_types( 'social' );
		if ( ! in_array( $post_type, $post_types, true ) ) {
			return;
		}

		$settings = Nelio_Content_Settings::instance();
		echo '<fieldset class="inline-edit-col-left clear">';
		echo '<div class="inline-edit-group wp-clearfix">';
		wp_nonce_field( 'nelio_content_quick_edit_post', 'nelio-content-quick-edit-post-nonce' );
		printf(
			'<div><input type="checkbox" name="nc_auto_share" %s /> <label for="nc_auto_share">%s</label>%s</div>',
			checked( 'include-in-auto-share', $settings->get( 'auto_share_default_mode' ), false ),
			esc_html_x( 'Auto share on social media with Nelio Content', 'command', 'nelio-content' ),
			wp_kses(
				$this->get_auto_share_end_select(),
				array(
					'div'    => array( 'class' => true ),
					'select' => array( 'name' => true ),
					'option' => array( 'value' => true ),
				)
			)
		);
		echo '</div></fieldset>';
	}

	/**
	 * Callback to update auto share settings.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 *
	 * @return void
	 */
	public function update_auto_share_on_quick_or_bulk_edit( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$post_types = nelio_content_get_post_types( 'social' );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['nelio-content-quick-edit-post-nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['nelio-content-quick-edit-post-nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'nelio_content_quick_edit_post' ) ) {
			return;
		}

		$auto_share = false;
		if ( isset( $_REQUEST['nc_auto_share'] ) ) {
			$auto_share = 'on' === sanitize_text_field( wp_unslash( $_REQUEST['nc_auto_share'] ) );
		}

		$end_mode = 'never';
		if ( isset( $_REQUEST['nc_auto_share_end_mode'] ) ) {
			$end_mode  = sanitize_text_field( wp_unslash( $_REQUEST['nc_auto_share_end_mode'] ) );
			$end_modes = array_map( fn( $m ) => $m['value'], nelio_content_get_auto_share_end_modes() );
			if ( ! in_array( $end_mode, $end_modes, true ) ) {
				$end_mode = 'never';
			}
		}

		$aux = Nelio_Content_Post_Helper::instance();
		$aux->enable_auto_share( $post_id, $auto_share );
		$aux->update_auto_share_end_mode( $post_id, $end_mode );
	}

	/**
	 * Callback to customize row actions.
	 *
	 * @param array<string,string> $actions Actions.
	 * @param WP_Post              $post    Post.
	 *
	 * @return array<string,string>
	 */
	public function customize_row_actions( $actions, $post ) {
		$post_types = nelio_content_get_post_types( 'social' );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return $actions;
		}

		$social_permission = nelio_content_get_social_editor_permission();
		$can_edit_social   = (
		'all' === $social_permission ||
		( 'post-type' === $social_permission && current_user_can( 'edit_post', $post->ID ) )
		);

		if ( $can_edit_social ) {
			$actions['nc-share'] = sprintf(
				'<span class="nelio-content-share-post" data-post-id="%s" title="%s">%s</span>',
				esc_attr( $post->ID ),
				esc_attr( _x( 'Social Media', 'text', 'nelio-content' ) ),
				esc_html( _x( 'Social Media', 'text', 'nelio-content' ) )
			);
		} else {
			$actions['nc-share'] = sprintf(
				'<span>%s</span>',
				esc_html( _x( 'Social Media', 'text', 'nelio-content' ) )
			);
		}

		if ( empty( $actions['view'] ) ) {
			return $actions;
		}

		$custom_statuses = $this->get_post_custom_statuses( $post->post_type );
		$custom_statuses = wp_list_pluck( $custom_statuses, 'slug' );
		$custom_statuses = array_values( array_unique( $custom_statuses ) );
		if ( in_array( $post->post_status, $custom_statuses, true ) ) {
			$title        = _draft_or_post_title( $post );
			$preview_link = get_preview_post_link( $post );
			if ( $preview_link ) {
				$actions['view'] = sprintf(
					'<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
					esc_url( $preview_link ),
					// phpcs:ignore WordPress.WP.I18n.MissingTranslatorsComment, WordPress.WP.I18n.MissingArgDomain
					esc_attr( sprintf( __( 'Preview &#8220;%s&#8221;' ), $title ) ),
					// phpcs:ignore WordPress.WP.I18n.MissingArgDomain
					__( 'Preview' )
				);
			}
		}

		return $actions;
	}

	/**
	 * Callback function to display custom post states.
	 *
	 * @param array<string,string> $states States.
	 * @param WP_Post              $post   Post.
	 *
	 * @return array<string,string>
	 */
	public function display_post_custom_states( $states, $post ) {
		$custom_statuses       = $this->get_post_custom_statuses( $post->post_type );
		$custom_statuses_slugs = wp_list_pluck( $custom_statuses, 'slug' );
		$custom_statuses_slugs = array_values( array_unique( $custom_statuses_slugs ) );

		if ( empty( $custom_statuses_slugs ) ) {
			return $states;
		}

		if ( ! in_array( $post->post_status, $custom_statuses_slugs, true ) ) {
			return $states;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST['post_status'] ) && $post->post_status === $_REQUEST['post_status'] ) {
			return $states;
		}

		if ( in_array( $post->post_status, $custom_statuses_slugs, true ) ) {
			$custom_status = array_filter(
				$custom_statuses,
				function ( $status ) use ( $post ) {
					return $status['slug'] === $post->post_status;
				}
			);
			$custom_status = array_shift( $custom_status );
			if ( ! empty( $custom_status['name'] ) ) {
				$states[ $post->post_status ] = $custom_status['name'];
			}
		}

		return $states;
	}

	/**
	 * Returns auto share end mode selector.
	 *
	 * @return string
	 */
	private function get_auto_share_end_select() {
		$options = nelio_content_get_auto_share_end_modes();

		$res = '<div class="nc-auto-share-end"><select name="nc_auto_share_end_mode">';
		foreach ( $options as $option ) {
			$res .= sprintf(
				'<option value="%s">%s</option>',
				esc_attr( $option['value'] ),
				esc_html( $option['label'] )
			);
		}
		$res .= '</select></div>';
		return $res;
	}

	/**
	 * Returns custom statuses for the given post type.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return list<TPost_Status>
	 */
	private function get_post_custom_statuses( $post_type ) {
		$post_statuses   = nelio_content_get_post_statuses( $post_type );
		$custom_statuses = array_values(
			array_filter(
				$post_statuses,
				function ( $status ) {
					return ! in_array( $status['slug'], array( 'draft', 'pending', 'future', 'private', 'publish', 'trash', 'nelio-content-unscheduled' ), true );
				}
			)
		);
		return $custom_statuses;
	}

	/**
	 * Whether current screen is post list and the post type is included in Nelio Content’s social context.
	 *
	 * @return bool
	 */
	private function is_current_screen_social_post_list() {

		$screen = get_current_screen();
		if ( ! isset( $screen->id ) ) {
			return false;
		}

		$screen = $screen->id;

		if ( strpos( $screen, 'edit-' ) !== 0 ) {
			return false;
		}

		$post_types = nelio_content_get_post_types( 'social' );
		$screen     = preg_replace( '/^edit-/', '', $screen );
		if ( ! in_array( $screen, $post_types, true ) ) {
			return false;
		}

		return true;
	}
}

<?php
 if ( ! defined( 'ABSPATH' ) ) { exit; } function hvm_logo_groups() { return array( 'previous_partners' => 'Previous Partner logos', 'media_partners' => 'Media Partner logos', ); } function hvm_theme_logos( $key ) { $opt = get_option( 'hvm_theme_logos', array() ); return ( isset( $opt[ $key ] ) && is_array( $opt[ $key ] ) ) ? $opt[ $key ] : array(); } function hvm_team_categories() { return array( 'marketing' => 'Hivemind Marketing', 'world' => 'Hivemind World', ); } function hvm_team_members( $category = '' ) { $all = get_option( 'hvm_team', array() ); if ( ! is_array( $all ) ) { return array(); } if ( '' === $category ) { return $all; } return array_values( array_filter( $all, function ( $m ) use ( $category ) { if ( isset( $m['categories'] ) && is_array( $m['categories'] ) ) { return in_array( $category, $m['categories'], true ); } return isset( $m['category'] ) && $category === $m['category']; } ) ); } function hvm_advisor_members() { $all = get_option( 'hvm_advisor', array() ); return is_array( $all ) ? $all : array(); } add_action( 'admin_init', function () { register_setting( 'hvm_theme_logos_group', 'hvm_theme_logos', array( 'type' => 'array', 'sanitize_callback' => 'hvm_sanitize_theme_logos', 'default' => array(), ) ); register_setting( 'hvm_theme_logos_group', 'hvm_team', array( 'type' => 'array', 'sanitize_callback' => 'hvm_sanitize_team', 'default' => array(), ) ); register_setting( 'hvm_theme_logos_group', 'hvm_advisor', array( 'type' => 'array', 'sanitize_callback' => 'hvm_sanitize_advisor', 'default' => array(), ) ); } ); function hvm_sanitize_theme_logos( $in ) { $out = array(); foreach ( array_keys( hvm_logo_groups() ) as $k ) { $val = isset( $in[ $k ] ) ? (string) $in[ $k ] : ''; $out[ $k ] = array_values( array_filter( array_map( 'absint', explode( ',', $val ) ) ) ); } return $out; } function hvm_sanitize_team( $in ) { $out = array(); if ( ! is_array( $in ) ) { return $out; } $cats = array_keys( hvm_team_categories() ); foreach ( $in as $row ) { if ( ! is_array( $row ) ) { continue; } $name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : ''; $photo = isset( $row['photo'] ) ? absint( $row['photo'] ) : 0; if ( '' === $name && ! $photo ) { continue; } $sel = isset( $row['categories'] ) && is_array( $row['categories'] ) ? $row['categories'] : array(); if ( empty( $sel ) && isset( $row['category'] ) ) { $sel = array( $row['category'] ); } $member_cats = array_values( array_intersect( $cats, array_map( 'sanitize_text_field', $sel ) ) ); if ( empty( $member_cats ) ) { $member_cats = array( $cats[0] ); } $out[] = array( 'photo' => $photo, 'name' => $name, 'role' => isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : '', 'bio' => isset( $row['bio'] ) ? sanitize_textarea_field( $row['bio'] ) : '', 'linkedin' => isset( $row['linkedin'] ) ? esc_url_raw( $row['linkedin'] ) : '', 'categories' => $member_cats, ); } return $out; } function hvm_sanitize_advisor( $in ) { $out = array(); if ( ! is_array( $in ) ) { return $out; } foreach ( $in as $row ) { if ( ! is_array( $row ) ) { continue; } $name = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : ''; $photo = isset( $row['photo'] ) ? absint( $row['photo'] ) : 0; if ( '' === $name && ! $photo ) { continue; } $out[] = array( 'photo' => $photo, 'name' => $name, 'role' => isset( $row['role'] ) ? sanitize_text_field( $row['role'] ) : '', 'bio' => isset( $row['bio'] ) ? sanitize_textarea_field( $row['bio'] ) : '', 'linkedin' => isset( $row['linkedin'] ) ? esc_url_raw( $row['linkedin'] ) : '', ); } return $out; } add_action( 'admin_menu', function () { add_menu_page( 'DoitDigital Theme Options', 'Theme Options', 'manage_options', 'hvm-theme-options', 'hvm_render_theme_options_page', 'dashicons-admin-customizer', 59 ); } ); add_action( 'admin_enqueue_scripts', function ( $hook ) { if ( 'toplevel_page_hvm-theme-options' !== $hook ) { return; } wp_enqueue_media(); wp_enqueue_script( 'jquery-ui-sortable' ); wp_enqueue_style( 'hvm-admin', HVM_URI . '/inc/admin.css', array(), HVM_VER ); wp_enqueue_script( 'hvm-admin', HVM_URI . '/inc/admin.js', array( 'jquery', 'jquery-ui-sortable' ), HVM_VER, true ); } ); function hvm_render_logo_picker( $key ) { $ids = hvm_theme_logos( $key ); echo '<div class="hvm-gallery">'; printf( '<input type="hidden" class="hvm-gallery-ids" name="hvm_theme_logos[%s]" value="%s" />', esc_attr( $key ), esc_attr( implode( ',', $ids ) ) ); echo '<div class="hvm-gallery-preview">'; foreach ( $ids as $id ) { $s = wp_get_attachment_image_url( $id, 'thumbnail' ); if ( $s ) { printf( '<img src="%s" />', esc_url( $s ) ); } } echo '</div>'; echo '<button type="button" class="button button-primary hvm-gallery-select">Add / edit logos</button> '; echo '<button type="button" class="button hvm-gallery-clear">Clear</button>'; echo '</div>'; } function hvm_render_team_row( $index, $row ) { $fields = array( array( 'key' => 'photo', 'label' => 'Photo', 'type' => 'image' ), array( 'key' => 'name', 'label' => 'Name', 'type' => 'text' ), array( 'key' => 'role', 'label' => 'Role / Title', 'type' => 'text' ), array( 'key' => 'bio', 'label' => 'Bio', 'type' => 'textarea' ), array( 'key' => 'linkedin', 'label' => 'LinkedIn URL', 'type' => 'url' ), ); echo '<div class="hvm-row">'; echo '<span class="hvm-row-handle dashicons dashicons-menu"></span>'; echo '<button type="button" class="button-link hvm-row-remove" title="Remove">&times;</button>'; echo '<div class="hvm-row-fields">'; $sel = isset( $row['categories'] ) && is_array( $row['categories'] ) ? $row['categories'] : ( isset( $row['category'] ) ? array( $row['category'] ) : array() ); echo '<div class="hvm-sub hvm-sub-cats"><span>Teams (select one or more)</span><span class="hvm-cat-boxes">'; foreach ( hvm_team_categories() as $ck => $cl ) { printf( '<label class="hvm-cat-check"><input type="checkbox" name="hvm_team[%s][categories][]" value="%s"%s /> %s</label>', esc_attr( $index ), esc_attr( $ck ), checked( in_array( $ck, $sel, true ), true, false ), esc_html( $cl ) ); } echo '</span></div>'; foreach ( $fields as $sf ) { $name = sprintf( 'hvm_team[%s][%s]', $index, $sf['key'] ); $val = isset( $row[ $sf['key'] ] ) ? $row[ $sf['key'] ] : ''; echo '<label class="hvm-sub"><span>' . esc_html( $sf['label'] ) . '</span>'; hvm_render_field( $sf, $name, $val ); echo '</label>'; } echo '</div></div>'; } function hvm_render_advisor_row( $index, $row ) { $fields = array( array( 'key' => 'photo', 'label' => 'Photo', 'type' => 'image' ), array( 'key' => 'name', 'label' => 'Name', 'type' => 'text' ), array( 'key' => 'role', 'label' => 'Role / Title', 'type' => 'text' ), array( 'key' => 'bio', 'label' => 'Bio', 'type' => 'textarea' ), array( 'key' => 'linkedin', 'label' => 'LinkedIn URL', 'type' => 'url' ), ); echo '<div class="hvm-row">'; echo '<span class="hvm-row-handle dashicons dashicons-menu"></span>'; echo '<button type="button" class="button-link hvm-row-remove" title="Remove">&times;</button>'; echo '<div class="hvm-row-fields">'; foreach ( $fields as $sf ) { $name = sprintf( 'hvm_advisor[%s][%s]', $index, $sf['key'] ); $val = isset( $row[ $sf['key'] ] ) ? $row[ $sf['key'] ] : ''; echo '<label class="hvm-sub"><span>' . esc_html( $sf['label'] ) . '</span>'; hvm_render_field( $sf, $name, $val ); echo '</label>'; } echo '</div></div>'; } function hvm_render_theme_options_page() { if ( ! current_user_can( 'manage_options' ) ) { return; } $logo_groups = hvm_logo_groups(); $tabs = array(); foreach ( $logo_groups as $k => $label ) { $tabs[ $k ] = $label; } $tabs['team'] = 'Team'; $tabs['advisor'] = 'Advisor'; $team = hvm_team_members(); $advisors = hvm_advisor_members(); ?>
	<div class="wrap hvm-options">
		<h1>DoitDigital Theme Options</h1>
		<p class="description">Manage the global logo sets and the Team roster — the Mixer widgets render them site-wide.</p>

		<form action="options.php" method="post">
			<?php settings_fields( 'hvm_theme_logos_group' ); ?>

			<div class="hvm-vtabs">
				<nav class="hvm-vnav">
					<?php $first = true; foreach ( $tabs as $k => $label ) : ?>
						<a href="#hvm-tab-<?php echo esc_attr( $k ); ?>" class="hvm-vnav-item<?php echo $first ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php $first = false; endforeach; ?>
				</nav>

				<div class="hvm-vpanels">
					<?php $first = true; foreach ( $logo_groups as $k => $label ) : ?>
						<div class="hvm-opt-panel<?php echo $first ? ' is-active' : ''; ?>" id="hvm-tab-<?php echo esc_attr( $k ); ?>">
							<h2><?php echo esc_html( $label ); ?></h2>
							<p class="description">Select the logos for the <strong><?php echo esc_html( $label ); ?></strong> widget. Drag in the media library to reorder.</p>
							<?php hvm_render_logo_picker( $k ); ?>
						</div>
					<?php $first = false; endforeach; ?>

					<div class="hvm-opt-panel" id="hvm-tab-team">
						<h2>Team</h2>
						<p class="description">Add team members and assign each to a team (<strong>Hivemind Marketing</strong> or <strong>Hivemind World</strong>). The Team widget renders them.</p>
						<div class="hvm-repeater hvm-team-repeater" data-key="team">
							<div class="hvm-rows">
								<?php foreach ( $team as $i => $row ) { hvm_render_team_row( $i, $row ); } ?>
							</div>
							<script type="text/html" class="hvm-row-tpl"><?php hvm_render_team_row( '__i__', array() ); ?></script>
							<button type="button" class="button button-secondary hvm-row-add">+ Add team member</button>
						</div>
					</div>

					<div class="hvm-opt-panel" id="hvm-tab-advisor">
						<h2>Advisor</h2>
						<p class="description">Add advisors (a flat list, no teams). Choose <strong>Advisor</strong> as the source on the Team widget to render them.</p>
						<div class="hvm-repeater hvm-advisor-repeater" data-key="advisor">
							<div class="hvm-rows">
								<?php foreach ( $advisors as $i => $row ) { hvm_render_advisor_row( $i, $row ); } ?>
							</div>
							<script type="text/html" class="hvm-row-tpl"><?php hvm_render_advisor_row( '__i__', array() ); ?></script>
							<button type="button" class="button button-secondary hvm-row-add">+ Add advisor</button>
						</div>
					</div>
				</div>
			</div>

			<?php submit_button( 'Save Theme Options' ); ?>
		</form>
	</div>

	<style>
		.hvm-vtabs{display:flex;gap:24px;align-items:flex-start;margin:16px 0;}
		.hvm-vnav{flex:0 0 220px;display:flex;flex-direction:column;background:#fff;border:1px solid #dcdcde;border-radius:8px;overflow:hidden;position:sticky;top:46px;}
		.hvm-vnav-item{display:block;padding:12px 16px;text-decoration:none;color:#1d2327;border-left:3px solid transparent;border-bottom:1px solid #f0f0f1;}
		.hvm-vnav-item:last-child{border-bottom:0;}
		.hvm-vnav-item:focus{box-shadow:none;outline:none;}
		.hvm-vnav-item.is-active{background:#f6f7f7;border-left-color:#2271b1;font-weight:600;color:#2271b1;}
		.hvm-vpanels{flex:1 1 auto;min-width:0;}
		.hvm-opt-panel{display:none;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px 24px;}
		.hvm-opt-panel.is-active{display:block;}
		.hvm-options .hvm-gallery-preview{display:flex;flex-wrap:wrap;gap:10px;margin:12px 0;}
		.hvm-options .hvm-gallery-preview img{width:90px;height:70px;object-fit:contain;background:#f6f7f7;border:1px solid #e2e4e7;border-radius:6px;padding:6px;}
		.hvm-team-repeater .hvm-row{position:relative;background:#fbfbfc;border:1px solid #e2e4e7;border-radius:8px;padding:16px 16px 8px 36px;margin:0 0 14px;}
		.hvm-team-repeater .hvm-row-handle{position:absolute;left:10px;top:16px;color:#a7aaad;cursor:move;}
		.hvm-team-repeater .hvm-row-remove{position:absolute;right:12px;top:10px;color:#b32d2e;font-size:20px;text-decoration:none;line-height:1;}
		.hvm-team-repeater .hvm-row-fields{display:grid;grid-template-columns:repeat(2,1fr);gap:10px 18px;}
		.hvm-team-repeater .hvm-sub{display:flex;flex-direction:column;font-size:12px;font-weight:600;color:#50575e;}
		.hvm-team-repeater .hvm-sub span{margin-bottom:3px;}
		.hvm-team-repeater .hvm-sub textarea{min-height:56px;}
		.hvm-team-repeater .hvm-sub-cats{grid-column:1 / -1;}
		.hvm-team-repeater .hvm-cat-boxes{display:flex;flex-wrap:wrap;gap:8px 18px;margin-top:2px;}
		.hvm-team-repeater .hvm-cat-check{display:inline-flex;align-items:center;gap:6px;font-weight:500;color:#1d2327;}
		.hvm-team-repeater .hvm-cat-check input{margin:0;}
		.hvm-team-repeater .hvm-image-preview{max-width:70px;max-height:70px;display:block;margin:4px 0;border:1px solid #e2e4e7;border-radius:6px;background:#f6f7f7;}
	</style>
	<script>
	( function () {
		var items  = document.querySelectorAll( '.hvm-vnav-item' );
		var panels = document.querySelectorAll( '.hvm-opt-panel' );
		items.forEach( function ( t ) {
			t.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				items.forEach( function ( x ) { x.classList.remove( 'is-active' ); } );
				panels.forEach( function ( p ) { p.classList.remove( 'is-active' ); } );
				t.classList.add( 'is-active' );
				var el = document.getElementById( 'hvm-tab-' + t.getAttribute( 'data-tab' ) );
				if ( el ) { el.classList.add( 'is-active' ); }
			} );
		} );
	} )();
	</script>
	<?php
} 
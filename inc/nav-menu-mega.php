<?php
 if ( ! defined( 'ABSPATH' ) ) { exit; } class HVM_Nav_Mega { public static $parents = array(); const META_MEGA = '_hvm_menu_mega'; const META_IMG = '_hvm_menu_img'; const META_SUB = '_hvm_menu_sub'; public static function init() { add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'fields' ), 10, 2 ); add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save' ), 10, 2 ); add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) ); add_action( 'admin_footer-nav-menus.php', array( __CLASS__, 'admin_js' ) ); add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'detect_parents' ), 10, 2 ); add_filter( 'nav_menu_css_class', array( __CLASS__, 'parent_class' ), 10, 2 ); add_filter( 'walker_nav_menu_start_el', array( __CLASS__, 'render_item' ), 10, 4 ); add_action( 'wp_enqueue_scripts', array( __CLASS__, 'front_assets' ), 20 ); } public static function fields( $item_id, $item ) { $mega = (bool) get_post_meta( $item_id, self::META_MEGA, true ); $img = (int) get_post_meta( $item_id, self::META_IMG, true ); $sub = (string) get_post_meta( $item_id, self::META_SUB, true ); $imgurl = $img ? wp_get_attachment_image_url( $img, 'thumbnail' ) : ''; ?>
		<p class="field-hvm-menu-mega description description-wide">
			<label>
				<input type="checkbox" name="hvm_menu_mega[<?php echo esc_attr( $item_id ); ?>]" value="1"<?php checked( $mega ); ?> />
				<?php esc_html_e( 'Mega dropdown — show this item\'s submenu as image cards', 'hivemind-mixer' ); ?>
			</label>
			<span class="description" style="display:block;margin-left:24px;color:#787c82;"><?php esc_html_e( 'Tick this on the PARENT item (e.g. Resources, Events). Child items below can each set an image + subtitle.', 'hivemind-mixer' ); ?></span>
		</p>
		<p class="field-hvm-menu-img description description-wide">
			<label><?php esc_html_e( 'Dropdown image', 'hivemind-mixer' ); ?><br />
				<span class="hvm-menu-img-preview" style="display:inline-block;vertical-align:middle;margin:4px 8px 4px 0;">
					<?php if ( $imgurl ) : ?>
						<img src="<?php echo esc_url( $imgurl ); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" />
					<?php endif; ?>
				</span>
				<input type="hidden" class="hvm-menu-img-id" name="hvm_menu_img[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $img ); ?>" />
				<button type="button" class="button hvm-menu-img-upload"><?php esc_html_e( 'Choose image', 'hivemind-mixer' ); ?></button>
				<button type="button" class="button hvm-menu-img-remove"<?php echo $img ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'hivemind-mixer' ); ?></button>
			</label>
		</p>
		<p class="field-hvm-menu-sub description description-wide">
			<label><?php esc_html_e( 'Subtitle (optional)', 'hivemind-mixer' ); ?><br />
				<input type="text" class="widefat" name="hvm_menu_sub[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $sub ); ?>" placeholder="<?php esc_attr_e( '(Invites, discounts & tips)', 'hivemind-mixer' ); ?>" />
			</label>
		</p>
		<?php
 } public static function save( $menu_id, $item_id ) { if ( ! current_user_can( 'edit_theme_options' ) ) { return; } $mega = ! empty( $_POST['hvm_menu_mega'][ $item_id ] ); $img = isset( $_POST['hvm_menu_img'][ $item_id ] ) ? absint( $_POST['hvm_menu_img'][ $item_id ] ) : 0; $sub = isset( $_POST['hvm_menu_sub'][ $item_id ] ) ? sanitize_text_field( wp_unslash( $_POST['hvm_menu_sub'][ $item_id ] ) ) : ''; if ( $mega ) { update_post_meta( $item_id, self::META_MEGA, 1 ); } else { delete_post_meta( $item_id, self::META_MEGA ); } if ( $img ) { update_post_meta( $item_id, self::META_IMG, $img ); } else { delete_post_meta( $item_id, self::META_IMG ); } if ( '' !== $sub ) { update_post_meta( $item_id, self::META_SUB, $sub ); } else { delete_post_meta( $item_id, self::META_SUB ); } } public static function admin_assets( $hook ) { if ( 'nav-menus.php' === $hook ) { wp_enqueue_media(); } } public static function admin_js() { ?>
		<script>
		( function ( $ ) {
			$( document ).on( 'click', '.hvm-menu-img-upload', function ( e ) {
				e.preventDefault();
				var $btn = $( this ), $wrap = $btn.closest( 'p' );
				var frame = wp.media( { title: 'Select image', multiple: false, library: { type: 'image' } } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
					$wrap.find( '.hvm-menu-img-id' ).val( a.id );
					$wrap.find( '.hvm-menu-img-preview' ).html( '<img src="' + url + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" />' );
					$wrap.find( '.hvm-menu-img-remove' ).show();
				} );
				frame.open();
			} );
			$( document ).on( 'click', '.hvm-menu-img-remove', function ( e ) {
				e.preventDefault();
				var $wrap = $( this ).closest( 'p' );
				$wrap.find( '.hvm-menu-img-id' ).val( '' );
				$wrap.find( '.hvm-menu-img-preview' ).empty();
				$( this ).hide();
			} );
		} )( jQuery );
		</script>
		<?php
 } public static function detect_parents( $items, $args ) { self::$parents = array(); $by_id = array(); foreach ( $items as $it ) { $by_id[ $it->ID ] = $it; } foreach ( $items as $it ) { if ( get_post_meta( $it->ID, self::META_MEGA, true ) ) { self::$parents[ (int) $it->ID ] = true; } } if ( empty( self::$parents ) ) { return $items; } $out = array(); $seen = array(); foreach ( $items as $it ) { $pid = (int) ( $it->menu_item_parent ?? 0 ); if ( $pid && isset( self::$parents[ $pid ] ) && empty( $seen[ $pid ] ) ) { $seen[ $pid ] = true; $parent = $by_id[ $pid ] ?? null; $h = clone $it; $h->ID = 'hvm-head-' . $pid; $h->db_id = 0; $h->object_id = 0; $h->title = $parent ? $parent->title : ''; $h->url = ''; $h->classes = array( 'hvm-mega-head-li' ); $h->hvm_heading = $parent ? $parent->title : ''; $out[] = $h; } $out[] = $it; } return $out; } public static function parent_class( $classes, $item ) { if ( isset( self::$parents[ (int) $item->ID ] ) ) { $classes[] = 'hvm-mega'; } return $classes; } public static function render_item( $item_output, $item, $depth, $args ) { if ( ! empty( $item->hvm_heading ) ) { return '<span class="hvm-mega-head">' . esc_html( $item->hvm_heading ) . '</span>'; } if ( empty( $item->menu_item_parent ) || ! isset( self::$parents[ (int) $item->menu_item_parent ] ) ) { return $item_output; } $img = (int) get_post_meta( $item->ID, self::META_IMG, true ); $sub = get_post_meta( $item->ID, self::META_SUB, true ); if ( '' === $sub && 'post_type' === ( $item->type ?? '' ) && ! empty( $item->object_id ) ) { $sub = self::event_date_sub( (int) $item->object_id ); } $imgurl = $img ? wp_get_attachment_image_url( $img, 'thumbnail' ) : ''; $title = isset( $item->title ) ? $item->title : ''; $url = ! empty( $item->url ) ? $item->url : '#'; $chev = '<svg class="hvm-mega-chev" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'; $out = '<a class="hvm-mega-item' . ( $imgurl ? '' : ' hvm-mega-item--noimg' ) . '" href="' . esc_url( $url ) . '">'; if ( $imgurl ) { $out .= '<span class="hvm-mega-ic"><img src="' . esc_url( $imgurl ) . '" alt="" loading="lazy" /></span>'; } $out .= '<span class="hvm-mega-txt"><span class="hvm-mega-title">' . esc_html( $title ) . '</span>'; if ( '' !== $sub ) { $out .= '<span class="hvm-mega-sub">' . esc_html( $sub ) . '</span>'; } $out .= '</span>'; $out .= $chev; $out .= '</a>'; return $out; } private static function event_date_sub( $post_id ) { if ( function_exists( 'hvm_event_datetime' ) ) { $dt = hvm_event_datetime( $post_id ); if ( ! empty( $dt['ts'] ) ) { return date_i18n( 'F j, Y', $dt['ts'] ); } } $raw = get_post_meta( $post_id, 'event_date', true ); if ( $raw ) { if ( is_numeric( $raw ) && 8 === strlen( (string) $raw ) ) { $raw = substr( $raw, 0, 4 ) . '-' . substr( $raw, 4, 2 ) . '-' . substr( $raw, 6, 2 ); } $ts = strtotime( $raw ); if ( $ts ) { return date_i18n( 'jS F Y', $ts ); } } return ''; } public static function front_assets() { if ( wp_style_is( 'hvm-mixer', 'registered' ) && ! wp_style_is( 'hvm-mixer', 'enqueued' ) ) { wp_enqueue_style( 'hvm-mixer' ); } } } HVM_Nav_Mega::init(); 
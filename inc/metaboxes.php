<?php
 if ( ! defined( 'ABSPATH' ) ) { exit; } add_action( 'add_meta_boxes', function () { foreach ( hvm_managed_cpts() as $cpt => $schema_cb ) { $obj = get_post_type_object( $cpt ); $label = $obj ? $obj->labels->singular_name : ucfirst( $cpt ); add_meta_box( 'hvm_' . $cpt . '_fields', $label . ' Details', 'hvm_render_all_metabox', $cpt, 'normal', 'high', array( 'cpt' => $cpt ) ); } } ); add_action( 'admin_enqueue_scripts', function ( $hook ) { if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && in_array( get_post_type(), array_keys( hvm_managed_cpts() ), true ) ) { wp_enqueue_media(); wp_enqueue_style( 'hvm-admin', HVM_URI . '/inc/admin.css', array(), HVM_VER ); wp_enqueue_script( 'hvm-admin', HVM_URI . '/inc/admin.js', array( 'jquery' ), HVM_VER, true ); } } ); function hvm_render_field( $field, $name, $value ) { $type = $field['type']; switch ( $type ) { case 'textarea': printf( '<textarea name="%s" rows="3" class="widefat">%s</textarea>', esc_attr( $name ), esc_textarea( (string) $value ) ); break; case 'url': printf( '<input type="url" name="%s" value="%s" class="widefat" placeholder="https://" />', esc_attr( $name ), esc_attr( (string) $value ) ); break; case 'number': printf( '<input type="number" step="0.1" name="%s" value="%s" />', esc_attr( $name ), esc_attr( (string) $value ) ); break; case 'datetime': printf( '<input type="datetime-local" name="%s" value="%s" />', esc_attr( $name ), esc_attr( (string) $value ) ); break; case 'time': printf( '<input type="time" name="%s" value="%s" />', esc_attr( $name ), esc_attr( (string) $value ) ); break; case 'image': $id = absint( $value ); $src = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : ''; echo '<div class="hvm-image">'; printf( '<input type="hidden" class="hvm-image-id" name="%s" value="%s" />', esc_attr( $name ), esc_attr( $id ) ); printf( '<img class="hvm-image-preview" src="%s" style="%s" />', esc_url( $src ), $src ? '' : 'display:none' ); echo '<button type="button" class="button hvm-image-select">Select image</button> '; echo '<button type="button" class="button hvm-image-remove"' . ( $src ? '' : ' style="display:none"' ) . '>Remove</button>'; echo '</div>'; break; case 'gallery': $ids = is_array( $value ) ? $value : array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ); echo '<div class="hvm-gallery">'; printf( '<input type="hidden" class="hvm-gallery-ids" name="%s" value="%s" />', esc_attr( $name ), esc_attr( implode( ',', $ids ) ) ); echo '<div class="hvm-gallery-preview">'; foreach ( $ids as $gid ) { $s = wp_get_attachment_image_url( $gid, 'thumbnail' ); if ( $s ) { printf( '<img src="%s" />', esc_url( $s ) ); } } echo '</div>'; echo '<button type="button" class="button hvm-gallery-select">Add / edit gallery</button> '; echo '<button type="button" class="button hvm-gallery-clear">Clear</button>'; echo '</div>'; break; case 'post_multiselect': $pt = isset( $field['post_type'] ) ? $field['post_type'] : 'post'; $sel = is_array( $value ) ? array_map( 'absint', $value ) : array(); $list = get_posts( array( 'post_type' => $pt, 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC', 'post_status' => 'publish' ) ); echo '<select name="' . esc_attr( $name ) . '[]" multiple size="8" class="widefat" style="min-height:160px">'; if ( ! $list ) { echo '<option disabled>No ' . esc_html( $pt ) . 's found — create some first.</option>'; } foreach ( $list as $p ) { printf( '<option value="%d"%s>%s</option>', (int) $p->ID, in_array( (int) $p->ID, $sel, true ) ? ' selected' : '', esc_html( $p->post_title ) ); } echo '</select>'; echo '<span class="description" style="display:block;margin-top:4px;">Hold ⌘/Ctrl to select more than one. Order follows the list.</span>'; break; case 'checkbox': $gate = ! empty( $field['gate'] ) ? ' class="hvm-gate" data-gate="' . esc_attr( $field['key'] ) . '"' : ''; printf( '<label class="hvm-checkbox"><input type="checkbox" name="%s" value="1" %s%s /> %s</label>', esc_attr( $name ), checked( '1', (string) $value, false ), $gate, esc_html( isset( $field['toggle_label'] ) ? $field['toggle_label'] : '' ) ); break; case 'text': default: printf( '<input type="text" name="%s" value="%s" class="widefat" />', esc_attr( $name ), esc_attr( (string) $value ) ); break; } } function hvm_render_repeater_row( $field, $index, $row ) { echo '<div class="hvm-row">'; echo '<span class="hvm-row-handle dashicons dashicons-menu"></span>'; echo '<button type="button" class="button-link hvm-row-remove" title="Remove">&times;</button>'; echo '<div class="hvm-row-fields">'; foreach ( $field['subfields'] as $sf ) { $name = sprintf( 'hvm[%s][%s][%s]', $field['key'], $index, $sf['key'] ); $val = isset( $row[ $sf['key'] ] ) ? $row[ $sf['key'] ] : ''; echo '<label class="hvm-sub"><span>' . esc_html( $sf['label'] ) . '</span>'; hvm_render_field( $sf, $name, $val ); echo '</label>'; } echo '</div></div>'; } function hvm_render_group_fields( $post, $group ) { echo '<div class="hvm-fields">'; $gate_key = null; $gate_on = true; foreach ( $group['fields'] as $field ) { $value = get_post_meta( $post->ID, hvm_meta_key( $field['key'] ), true ); $is_gate = ( 'checkbox' === $field['type'] && ! empty( $field['gate'] ) ); $gattr = ''; if ( $gate_key && ! $is_gate ) { $gattr = ' data-gated-by="' . esc_attr( $gate_key ) . '"' . ( $gate_on ? '' : ' style="display:none;"' ); } if ( 'repeater' === $field['type'] ) { $rows = is_array( $value ) ? $value : array(); echo '<div class="hvm-repeater" data-key="' . esc_attr( $field['key'] ) . '"' . $gattr . '>'; echo '<strong class="hvm-repeater-label">' . esc_html( $field['label'] ) . '</strong>'; echo '<div class="hvm-rows">'; foreach ( $rows as $i => $row ) { hvm_render_repeater_row( $field, $i, $row ); } echo '</div>'; echo '<script type="text/html" class="hvm-row-tpl">'; hvm_render_repeater_row( $field, '__i__', array() ); echo '</script>'; echo '<button type="button" class="button button-secondary hvm-row-add">+ Add row</button>'; echo '</div>'; } else { $name = 'hvm[' . $field['key'] . ']'; echo '<label class="hvm-field"' . $gattr . '><span class="hvm-label">' . esc_html( $field['label'] ) . '</span>'; hvm_render_field( $field, $name, $value ); echo '</label>'; } if ( $is_gate ) { $gate_key = $field['key']; $gate_on = ! empty( $value ); } } echo '</div>'; } function hvm_render_all_metabox( $post, $box ) { $cpt = isset( $box['args']['cpt'] ) ? $box['args']['cpt'] : $post->post_type; $schema = hvm_schema_for( $cpt ); wp_nonce_field( 'hvm_save', 'hvm_nonce' ); ?>
	<div class="hvm-meta-tabs">
		<nav class="hvm-vnav">
			<?php $first = true; foreach ( $schema as $gid => $group ) : ?>
				<a href="#hvm-g-<?php echo esc_attr( $gid ); ?>" class="hvm-vnav-item<?php echo $first ? ' is-active' : ''; ?>" data-tab="<?php echo esc_attr( $gid ); ?>"><?php echo esc_html( $group['title'] ); ?></a>
			<?php $first = false; endforeach; ?>
		</nav>
		<div class="hvm-vpanels">
			<?php $first = true; foreach ( $schema as $gid => $group ) : ?>
				<div class="hvm-gpanel<?php echo $first ? ' is-active' : ''; ?>" id="hvm-g-<?php echo esc_attr( $gid ); ?>">
					<h2 class="hvm-gpanel-title"><?php echo esc_html( $group['title'] ); ?></h2>
					<?php hvm_render_group_fields( $post, $group ); ?>
				</div>
			<?php $first = false; endforeach; ?>
		</div>
	</div>

	<style>
		#hvm_<?php echo esc_attr( $cpt ); ?>_fields .inside { margin: 0; padding: 0; }
		.hvm-meta-tabs { display: flex; gap: 0; align-items: stretch; }
		.hvm-meta-tabs .hvm-vnav { flex: 0 0 210px; display: flex; flex-direction: column; background: #f6f7f7; border-right: 1px solid #dcdcde; }
		.hvm-meta-tabs .hvm-vnav-item { display: block; padding: 12px 16px; text-decoration: none; color: #1d2327; border-left: 3px solid transparent; border-bottom: 1px solid #e8e8ea; font-weight: 500; box-shadow: none; }
		.hvm-meta-tabs .hvm-vnav-item:focus { box-shadow: none; outline: none; }
		.hvm-meta-tabs .hvm-vnav-item.is-active { background: #fff; border-left-color: #2271b1; color: #2271b1; }
		.hvm-meta-tabs .hvm-vpanels { flex: 1 1 auto; min-width: 0; padding: 18px 20px; }
		.hvm-meta-tabs .hvm-gpanel { display: none; }
		.hvm-meta-tabs .hvm-gpanel.is-active { display: block; }
		.hvm-meta-tabs .hvm-gpanel-title { margin: 0 0 16px; font-size: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f1; }
	</style>
	<script>
	( function () {
		var box = document.getElementById( 'hvm_<?php echo esc_js( $cpt ); ?>_fields' );
		if ( ! box ) { return; }
		var items  = box.querySelectorAll( '.hvm-vnav-item' );
		var panels = box.querySelectorAll( '.hvm-gpanel' );
		items.forEach( function ( t ) {
			t.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				items.forEach( function ( x ) { x.classList.remove( 'is-active' ); } );
				panels.forEach( function ( p ) { p.classList.remove( 'is-active' ); } );
				t.classList.add( 'is-active' );
				var el = document.getElementById( 'hvm-g-' + t.getAttribute( 'data-tab' ) );
				if ( el ) { el.classList.add( 'is-active' ); }
			} );
		} );

		// Gate checkboxes: show/hide the fields that follow them.
		box.querySelectorAll( '.hvm-gate' ).forEach( function ( cb ) {
			var key = cb.getAttribute( 'data-gate' );
			var sync = function () {
				box.querySelectorAll( '[data-gated-by="' + key + '"]' ).forEach( function ( el ) {
					el.style.display = cb.checked ? '' : 'none';
				} );
			};
			cb.addEventListener( 'change', sync );
			sync();
		} );
	} )();
	</script>
	<?php
} foreach ( array_keys( hvm_managed_cpts() ) as $hvm_cpt ) { add_action( 'save_post_' . $hvm_cpt, 'hvm_save_meta' ); } function hvm_save_meta( $post_id ) { if ( ! isset( $_POST['hvm_nonce'] ) || ! wp_verify_nonce( $_POST['hvm_nonce'], 'hvm_save' ) ) { return; } if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; } if ( ! current_user_can( 'edit_post', $post_id ) ) { return; } $raw = isset( $_POST['hvm'] ) && is_array( $_POST['hvm'] ) ? wp_unslash( $_POST['hvm'] ) : array(); foreach ( hvm_flat_fields( hvm_schema_for( get_post_type( $post_id ) ) ) as $key => $field ) { $mk = hvm_meta_key( $key ); $in = isset( $raw[ $key ] ) ? $raw[ $key ] : null; if ( 'repeater' === $field['type'] ) { $clean = array(); if ( is_array( $in ) ) { foreach ( $in as $row ) { if ( ! is_array( $row ) ) { continue; } $crow = array(); $has = false; foreach ( $field['subfields'] as $sf ) { $v = isset( $row[ $sf['key'] ] ) ? hvm_sanitize_value( $sf['type'], $row[ $sf['key'] ] ) : ''; $crow[ $sf['key'] ] = $v; if ( '' !== $v && array() !== $v ) { $has = true; } } if ( $has ) { $clean[] = $crow; } } } update_post_meta( $post_id, $mk, $clean ); } elseif ( 'gallery' === $field['type'] ) { $ids = array_filter( array_map( 'absint', explode( ',', (string) $in ) ) ); update_post_meta( $post_id, $mk, array_values( $ids ) ); } else { update_post_meta( $post_id, $mk, hvm_sanitize_value( $field['type'], $in ) ); } } } function hvm_sanitize_value( $type, $value ) { switch ( $type ) { case 'textarea': return sanitize_textarea_field( (string) $value ); case 'url': return esc_url_raw( (string) $value ); case 'number': return '' === $value ? '' : (float) $value; case 'image': return absint( $value ); case 'datetime': return preg_replace( '/[^0-9T:\-]/', '', (string) $value ); case 'time': return preg_replace( '/[^0-9:]/', '', (string) $value ); case 'checkbox': return $value ? '1' : ''; case 'post_multiselect': return array_values( array_filter( array_map( 'absint', (array) $value ) ) ); case 'text': default: return sanitize_text_field( (string) $value ); } } 
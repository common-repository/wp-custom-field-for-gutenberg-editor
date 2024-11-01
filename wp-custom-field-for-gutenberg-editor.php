<?php

/*
 Plugin Name: WP Custom field for Gutenberg Editor
 Plugin URI: https://wordpress.org/plugins/wp-custom-field-for-gutenberg-editor/
 Description: The plugin facilitates the user to continue using the functionality of custom fields along with Gutenberg WordPress editor.
 Version: 1.7.1
 Author: Thedotstore  
 Author URI: http://thedotstore.com/
 Text Domain: wp-custom-field-for-gutenberg-editor
*/
/**
 * Check The Plugin is activate or not.
 *
 * @since 1.0
 *
 */
function wcfefg_plugin_activate()
{
    
    if ( !is_plugin_active( 'gutenberg/gutenberg.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        $error_msg = esc_html__( 'Gutenberg Wordpress Custom Fields requires to activate ', 'wp-custom-field-for-gutenberg-editor' );
        $error_msg .= sprintf( '<a href="%s" target="_blank">Gutenberg Plugin</a></br>', 'https://wordpress.org/plugins/gutenberg/' );
        $error_msg .= esc_html__( 'Please used default WordPress custom field if you not have activated Gutenberg.', 'wp-custom-field-for-gutenberg-editor' );
        $error_msg .= sprintf( '<a href="%s">Return</a>', admin_url( 'plugins.php' ) );
        wp_die( wp_kses( $error_msg, gutenberg_editor_allowed_html_tags() ) );
    }

}

register_activation_hook( __FILE__, 'wcfefg_plugin_activate' );

if ( function_exists( 'wcffge_fs' ) ) {
    wcffge_fs()->set_basename( false, __FILE__ );
    return;
}


if ( !function_exists( 'wcffge_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wcffge_fs()
    {
        global  $wcffge_fs ;
        
        if ( !isset( $wcffge_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $wcffge_fs = fs_dynamic_init( array(
                'id'             => '4765',
                'slug'           => 'wp-custom-field-for-gutenberg-editor',
                'type'           => 'plugin',
                'public_key'     => 'pk_d6735e270a4f441e6a4b459ae8506',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'first-path' => 'plugins.php',
                'support'    => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $wcffge_fs;
    }
    
    // Init Freemius.
    wcffge_fs();
    // Signal that SDK was initiated.
    do_action( 'wcffge_fs_loaded' );
    wcffge_fs()->add_action( 'after_uninstall', 'wcffge_fs_uninstall_cleanup' );
}

/**
 * Register meta box for Gutenberg custom fields.
 *
 * @since 1.0
 *
 */
function wcfefg_add_custom_fields_metabox()
{
    if ( !is_plugin_active( 'gutenberg/gutenberg.php' ) ) {
        return '';
    }
    $include_post_types = array( 'post', 'page' );
    foreach ( $include_post_types as $post_type ) {
        add_meta_box(
            'wcfefg_custom_fields',
            __( 'Custom Fields', 'wp-custom-field-for-gutenberg-editor' ),
            'wcfefg_custom_field_meta_box_markup',
            $post_type,
            'normal'
        );
    }
}

add_action( 'add_meta_boxes', 'wcfefg_add_custom_fields_metabox' );
/**
 * Display existing custom fields and add new custom field form.
 *
 * @since 1.0
 *
 * @param object $post
 */
function wcfefg_custom_field_meta_box_markup( $post )
{
    $post_ID = (int) $post->ID;
    $wcfefg_metadata = has_meta( $post_ID );
    if ( !empty($wcfefg_metadata) || count( $wcfefg_metadata ) > 0 ) {
        for ( $key = 0 ;  $key < count( $wcfefg_metadata ) ;  $key++ ) {
            if ( is_protected_meta( $wcfefg_metadata[$key]['meta_key'], 'post' ) || !current_user_can( 'edit_post_meta', $post->ID, $wcfefg_metadata[$key]['meta_key'] ) || empty($wcfefg_metadata[$key]['meta_value']) ) {
                unset( $wcfefg_metadata[$key] );
            }
        }
    }
    
    if ( !empty($wcfefg_metadata) || count( $wcfefg_metadata ) > 0 ) {
        ?>
		<table cellpadding="10" id="list-table">
			<thead>
			<tr>
				<th class="left"><?php 
        esc_html_e( 'Name', 'wp-custom-field-for-gutenberg-editor' );
        ?></th>
				<th><?php 
        esc_html_e( 'Value', 'wp-custom-field-for-gutenberg-editor' );
        ?></th>
			</tr>
			</thead>
			<tbody id="<?php 
        echo  count( $wcfefg_metadata ) ;
        ?>">
			<?php 
        foreach ( $wcfefg_metadata as $wcfefg_meta ) {
            echo  wp_kses( wcfefg_display_existing_meta( $wcfefg_meta ), gutenberg_editor_allowed_html_tags() ) ;
        }
        ?>
			</tbody>
		</table>
		<?php 
    } else {
        ?>
		<table cellpadding="10" id="list-table" style="display: none">
			<thead>
			<tr>
				<th class="left"><?php 
        esc_html_e( 'Name', 'wp-custom-field-for-gutenberg-editor' );
        ?></th>
				<th><?php 
        esc_html_e( 'Value', 'wp-custom-field-for-gutenberg-editor' );
        ?></th>
			</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
		<?php 
    }
    
    wcfefg_add_meta_form( $post );
    // WPCS: XSS OK.
}

/**
 * Print the custom fields in the Gutenberg Wordpress Custom Fields metabox.
 *
 * @since 1.0
 *
 * @param array $wcfefg_meta meta data.
 *
 * @return string
 */
function wcfefg_display_existing_meta( $wcfefg_meta )
{
    if ( is_protected_meta( $wcfefg_meta['meta_key'], 'post' ) ) {
        return '';
    }
    if ( is_serialized( $wcfefg_meta['meta_value'] ) ) {
        
        if ( is_serialized_string( $wcfefg_meta['meta_value'] ) ) {
            // This is a serialized string, so we should display it.
            $meta_value = 'meta_value';
            $wcfefg_meta[$meta_value] = maybe_unserialize( $wcfefg_meta[$meta_value] );
        } else {
            // This is a serialized array/object so we should NOT display it.
            return '';
        }
    
    }
    $wcfefg_meta_key = ( isset( $wcfefg_meta['meta_key'] ) ? $wcfefg_meta['meta_key'] : '' );
    $wcfefg_meta_value = ( isset( $wcfefg_meta['meta_value'] ) ? $wcfefg_meta['meta_value'] : '' );
    $wcfefg_meta_id = ( isset( $wcfefg_meta['meta_id'] ) ? $wcfefg_meta['meta_id'] : '' );
    $wcfefg_meta_form = '';
    
    if ( !empty($wcfefg_meta_key) && !empty($wcfefg_meta_value) ) {
        $wcfefg_delete_nonce = wp_create_nonce( 'delete-meta_' . $wcfefg_meta_id );
        $wcfefg_update_nonce = wp_create_nonce( 'add-meta' );
        $wcfefg_meta_delete_button = get_submit_button(
            __( 'Delete' ),
            'deletemeta small',
            "deletemeta[{$wcfefg_meta_id}]",
            false,
            array(
            'data-wp-lists' => "delete:the-list:meta-{$wcfefg_meta_id}::_ajax_nonce={$wcfefg_delete_nonce}",
        )
        );
        // WPCS: XSS OK.
        $wcfefg_meta_update_button = get_submit_button(
            __( 'Update' ),
            'updatemeta small',
            "meta-{$wcfefg_meta_id}-submit",
            false,
            array(
            'data-wp-lists' => "add:the-list:meta-{$wcfefg_meta_id}::_ajax_nonce-add-meta={$wcfefg_update_nonce}",
        )
        );
        // WPCS: XSS OK.
        $wcfefg_meta_form .= '<tr class="akil" id="meta-' . esc_attr( $wcfefg_meta_id ) . '">
                                        <td class="left">
                                            <label class="screen-reader-text" for="meta-' . esc_attr( $wcfefg_meta_id ) . '-key">' . esc_html__( 'Key', 'wp-custom-field-for-gutenberg-editor' ) . '</label>
                                            <input name="meta[' . esc_attr( $wcfefg_meta_id ) . '][key]" id="meta-' . esc_attr( $wcfefg_meta_id ) . '-key" type="text" size="20" value="' . esc_attr( $wcfefg_meta_key ) . '" />
                                            <br /><div class="submit">' . $wcfefg_meta_delete_button . "\n\t\t" . $wcfefg_meta_update_button . '</div> ' . wp_nonce_field(
            'wcfefg-change-meta-data',
            '_ajax_nonce',
            false,
            false
        ) . '
                                        </td>
                                        <td>
                                            <label class="screen-reader-text" for="meta-' . esc_attr( $wcfefg_meta_id ) . '-value">' . esc_html__( 'Value', 'wp-custom-field-for-gutenberg-editor' ) . '</label>
                                            <textarea name="meta[' . esc_attr( $wcfefg_meta_id ) . '][value]" id="meta-' . esc_attr( $wcfefg_meta_id ) . '-value" rows="2" cols="30">' . esc_textarea( $wcfefg_meta_value ) . '</textarea>
                                        </td>
                                    </tr>';
    }
    
    return $wcfefg_meta_form;
    // WPCS: XSS OK.
}

/**
 * Prints the form in the Gutenberg Wordpress Custom Fields meta box.
 *
 * @since 1.0
 *
 * @global wpdb   $wpdb WordPress database abstraction object.
 *
 * @param WP_Post $post .
 */
function wcfefg_add_meta_form( $post = null )
{
    $get_post = get_post( $post );
    $keys = apply_filters( 'postmeta_form_keys', null, $get_post );
    
    if ( null === $keys ) {
        $keys = [];
        $limit = apply_filters( 'postmeta_form_limit', 50 );
        $args = array(
            'post_type'        => 'any',
            'numberposts'      => $limit,
            'suppress_filters' => false,
        );
        $query = get_posts( $args );
        //phpcs:ignore
        foreach ( $query as $key => $mypost ) {
            $keys = array_unique( array_merge( $keys, array_keys( get_post_meta( $mypost->ID ) ) ) );
        }
    }
    
    
    if ( !empty($keys) || '' !== $keys ) {
        natcasesort( $keys );
        $meta_key_input_id = 'metakeyselect';
    } else {
        $meta_key_input_id = 'metakeyinput';
    }
    
    ?>
	<p><strong><?php 
    esc_html_e( 'Add New Custom Field:', 'wp-custom-field-for-gutenberg-editor' );
    ?></strong></p>
	<table cellpadding="10" id="newmeta">
		<thead>
		<tr>
			<th class="left">
				<label for="<?php 
    echo  esc_attr( $meta_key_input_id ) ;
    ?>"><?php 
    esc_html_e( 'Name', 'wp-custom-field-for-gutenberg-editor' );
    ?></label>
			</th>
			<th>
				<label for="<?php 
    esc_html_e( 'metavalue', 'wp-custom-field-for-gutenberg-editor' );
    ?>"><?php 
    esc_html_e( 'Value', 'wp-custom-field-for-gutenberg-editor' );
    ?></label>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td id="newmetaleft" class="left">
				<?php 
    
    if ( !empty($keys) || $keys !== '' ) {
        ?>
					<select id="metakeyselect" name="metakeyselect">
						<option value="#NONE#"><?php 
        esc_html_e( '&mdash; Select &mdash;', 'wp-custom-field-for-gutenberg-editor' );
        ?></option>
						<?php 
        foreach ( $keys as $key ) {
            if ( is_protected_meta( $key, 'post' ) || !current_user_can( 'add_post_meta', $post->ID, $key ) ) {
                continue;
            }
            echo  "<option value='" . esc_attr( $key ) . "'>" . esc_html( $key ) . "</option>" ;
        }
        ?>
					</select>
					<input class="hide-if-js" type="text" id="metakeyinput" name="metakeyinput" value="" />
					<a href="#postcustomstuff" class="hide-if-no-js" onclick="jQuery( '#metakeyinput, #metakeyselect, #enternew, #cancelnew' ).toggle();return false;">
						<br />
						<span id="enternew"><?php 
        esc_html_e( 'Enter new', 'wp-custom-field-for-gutenberg-editor' );
        ?></span>
						<span id="cancelnew" class="hidden"><?php 
        esc_html_e( 'Cancel', 'wp-custom-field-for-gutenberg-editor' );
        ?></span></a>
					<?php 
    } else {
        ?>
					<input type="text" id="metakeyinput" name="metakeyinput" value="" />
					<?php 
    }
    
    ?>
			</td>
			<td>
				<textarea id="metavalue" name="metavalue" rows="2" cols="25"></textarea>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="submit">
					<?php 
    submit_button(
        __( 'Add Custom Field', 'wp-custom-field-for-gutenberg-editor' ),
        '',
        'addmeta',
        false,
        array(
        'id' => 'newmeta-submit',
    )
    );
    ?>
				</div>
				<?php 
    wp_nonce_field( 'add-meta', '_ajax_nonce-add-meta', false );
    ?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php 
}

/**
 * Enqueue Script and Style for Gutenberg Wordpress Custom Fields.
 *
 * @since 1.0
 *
 * @global WP_Post $post .
 *
 */
function wcfefg_enqueue_style_script()
{
    global  $post ;
    $post_ID = (int) $post->ID;
    $jquery_deps = array( 'jquery', 'jquery-ui-core' );
    wp_enqueue_style( 'wcfefg-style', plugin_dir_url( __FILE__ ) . 'css/wp-custom-field-for-gutenberg-editor.css' );
    wp_enqueue_script(
        'wcfefg-action',
        plugin_dir_url( __FILE__ ) . 'js/wp-custom-field-for-gutenberg-editor.js',
        $jquery_deps,
        false,
        true
    );
    wp_localize_script( 'wcfefg-action', 'wcfefg_action_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'post_id'  => $post_ID,
    ) );
}

add_action( 'admin_print_styles-post.php', 'wcfefg_enqueue_style_script' );
add_action( 'admin_print_styles-post-new.php', 'wcfefg_enqueue_style_script' );
add_action(
    'plugin_row_meta',
    'wcfge_plugin_row_meta',
    10,
    2
);

function wcfge_plugin_row_meta( $links, $file )
{
    
    if ( false !== strpos( $file, 'wp-custom-field-for-gutenberg-editor.php' ) ) {
        $new_links = array(
            'support' => '<a href="https://www.thedotstore.com/support/" target="_blank">Support</a>',
        );
        $links = array_merge( $links, $new_links );
    }
    
    return $links;
}

function gutenberg_editor_allowed_html_tags( $tags = array() )
{
    $allowed_tags = array(
        'a'        => array(
        'href'   => array(),
        'title'  => array(),
        'class'  => array(),
        'target' => array(),
    ),
        'ul'       => array(
        'class' => array(),
    ),
        'label'    => array(
        'class' => array(),
        'for'   => array(),
    ),
        'table'    => array(
        'class' => array(),
        'for'   => array(),
    ),
        'tr'       => array(
        'class' => array(),
        'id'    => array(),
        'for'   => array(),
    ),
        'td'       => array(
        'class' => array(),
        'for'   => array(),
    ),
        'li'       => array(
        'class' => array(),
    ),
        'div'      => array(
        'class' => array(),
        'id'    => array(),
    ),
        'select'   => array(
        'id'       => array(),
        'name'     => array(),
        'class'    => array(),
        'multiple' => array(),
        'style'    => array(),
    ),
        'input'    => array(
        'id'    => array(),
        'value' => array(),
        'name'  => array(),
        'class' => array(),
        'type'  => array(),
    ),
        'textarea' => array(
        'id'    => array(),
        'name'  => array(),
        'class' => array(),
    ),
        'option'   => array(
        'id'       => array(),
        'selected' => array(),
        'name'     => array(),
        'value'    => array(),
    ),
        'br'       => array(),
        'em'       => array(),
        'strong'   => array(),
    );
    if ( !empty($tags) ) {
        foreach ( $tags as $key => $value ) {
            $allowed_tags[$key] = $value;
        }
    }
    return $allowed_tags;
}

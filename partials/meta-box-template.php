<div id="postlink-meta-box">
	<label for="postlink-reverse-type"><?php _e( 'Post', 'postlink' ); ?></label>
	<input name="postlink-reverse-type" id="postlink-reverse-type" type="text" value="<?php echo esc_attr( $target_name ) ?>"/>
	<input name="postlink-reverse-type-id" id="postlink-reverse-type-id" type="hidden" value="<?php echo esc_attr( $target_id ) ?>"/>
	<?php wp_nonce_field( 'update_postlinks', 'postlink_update_nonce' ); ?>
</div>

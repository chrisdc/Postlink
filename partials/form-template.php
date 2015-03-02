<div id="postlink-form">
	<?php
		$api = new postlink_api();
		$links = $api->get_linked_posts( get_post() );
	?>

	<?php // Output existing connections. ?>
	<div id="postlink-types">
		<div id="delete-links">
		</div>
		<?php if ( is_array( $links ) ) : ?>
			<?php foreach ( $links as $type_id => $type ) : ?>
				<?php foreach ( $type as $rev_type_id => $sub_type ) : ?>
					<?php
						$i = -1; // For counting hidden inputs.
						$title = esc_html( get_the_title( $type_id ) );
						if ( 0 !== $rev_type_id ) {
							$title .= '<span class="rev-link"> / ' . esc_html( get_the_title( $rev_type_id ) ) . '</span>';
						}
					?>

					<!-- create box based on $type_id and $rev_type_id -->
					<?php
						printf(
							'<div class="postlink-type" data-type-id="%1$s" data-type-name="%2$s" data-rev-type-id="%3$s" data-rev-type-name="%4$s" data-combined="%5$s">',
							$type_id,
							esc_html( get_the_title( $type_id ) ),
							$rev_type_id,
							esc_html( get_the_title( $rev_type_id ) ),
							$type_id . '/' . $rev_type_id
						)
					?>
						<div class="postlink-rows">
							<a href="#" class="close-link" data-action="delete-type">&times; <span class="screen-reader-text"><?php _e( 'Delete this connection type', 'postlink' ) ;?></span></a>
							<h3><?php echo $title; ?></h3>
							<?php foreach ( $sub_type as $link ) : ?>
								<?php $i++; ?>
								<span class="postlink-connection">
									<?php echo esc_html( get_the_title( $link ) ); ?>
									<?php
										printf(
											'<input type="hidden" class="link-id-input" value="%1$s" name="postlink[%2$s][%3$s][%4$s]" />',
											esc_attr( $link ),
											esc_attr( $type_id ),
											esc_attr( $rev_type_id ),
											$i
										);
									?>
									<a href="#" class="delete-link" data-action="delete-link">
										<span class="screen-reader-text"><?php _e( 'Delete this link', 'postlink' ) ?></span>
										<span class="dashicons dashicons-dismiss" data-action="delete-link"></span>
									</a>
								</span>
							<?php endforeach; ?>
						</div>
						<div class="add-link">
							<label for="reverse-link-type-input"><?php _e( 'Post:', 'postlink' ); ?></label>
							<input name="link-name" class="link-name" type="text"/>
							<input name="link-id" class="link-id" type="hidden"/>
							<input type="button" value="<?php _e( 'Add Connection', 'postlink' ); ?>" class="button add-postlink" data-id="33" data-action="add-link"/>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<div class="postlink-type template" id="type-template">
		<div class="postlink-rows">
			<a href="#" class="close-link" data-action="delete-type">&times; <span class="screen-reader-text">Delete this connection type</span></a>
			<h3>Sample Type</h3>
			<!-- Links go here -->
		</div>
		<div class="add-link">
			<label for="reverse-link-type-input"><?php _e( 'Post:', 'postlink' ); ?></label>
			<input name="link-name" class="link-name" type="text"/>
			<input name="link-id" class="link-id" type="hidden"/>
			<input type="button" value="<?php _e( 'Add Connection', 'postlink' ); ?>" class="button add-postlink" data-id="33" data-action="add-link"/>
		</div>
	</div>

	<span class="postlink-connection template" id="row-template">
		<span class="postlink-label">An Example Post</span>
		<input type="hidden" class="link-id-input">
		<a href="#" class="delete-link" data-action="delete-link">
			<span class="screen-reader-text">Delete this link</span>
			<span class="dashicons dashicons-dismiss" data-action="delete-link"></span>
		</a>
	</span>

	<div class="add-type">
		<div class="form-block">
			<label for="link-type-input"><?php _e( 'Type:', 'postlink' ); ?></label>
			<input name="link-type-input" id="link-type-input" type="text"/>
			<input name="link-type-id-input" id="link-type-id-input"  type="hidden"/>
		</div>

		<div class="form-block">
			<label for="reverse-link-type-input"><?php _e( 'Reverse Type (optional):', 'postlink' ); ?></label>
			<input name="reverse-link-type-input" id="reverse-link-type-input" type="text"/>
			<input name="reverse-link-type-id-input" id="reverse-link-type-id-input"  type="hidden"/>
		</div>

		<?php wp_nonce_field( 'update_postlinks', 'postlink_update_nonce' ); ?>
		<span><input id="link-type-button" type="button" value="Add Type" class="button-primary" data-action="add-type"/></span>
		<span id="postlink-errors"></span>
	</div>
</div>

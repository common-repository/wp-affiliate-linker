<div class="wrap">
    <h2>
    	<?php echo $this->plugin->displayName; ?> 
    	&raquo; 
    	<?php echo _e( 'Addons', $this->plugin->name ); ?>
    </h2>

    <?php
    // Notices
    foreach ( $this->notices as $type => $notices_type ) {
        if ( count( $notices_type ) == 0 ) {
            continue;
        }
        ?>
        <div class="<?php echo ( ( $type == 'success' ) ? 'updated' : 'error' ); ?> notice">
            <?php
            foreach ( $notices_type as $notice ) {
                ?>
                <p><?php echo $notice; ?></p>
                <?php
            }
            ?>
        </div>
        <?php
    }
    ?>

   	<div id="poststuff">
    	<div id="post-body" class="metabox-holder columns-1">
    		<!-- Content -->
    		<div id="post-body-content">
	            <div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
	            	<!-- Licensing -->
	            	<div id="licensing" class="sub-panel">
						<div class="postbox">
					    	<h3 class="hndle"><?php _e( 'License Key', $this->plugin->name ); ?></h3>

						    <form name="post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="<?php echo $this->plugin->name; ?>">  
							    <div class="option">
								    <?php
								    // If the license key is defined in wp-config, just display it here and don't provide any options.
								    if ( $screen['data']['license_key_is_constant'] ) {
								    	?>
								    	<p class="description">
								    		<?php _e( 'Your license key is defined in your wp-config.php file; to change it, edit that file.', $this->plugin->name ); ?>
								    	</p>
				                        <?php
								    } else {
								    	?>
								    	<p class="description">
									    	<?php 
									    	echo sprintf( __( 'If you have purchased a license for %s, enter your license key below. This will then enable installation and activation options for the Addons below.', $this->plugin->name ), $this->plugin->displayName ); 
									    	?>
									    </p>
									    <?php
								    }          
								    ?>
							    	<input type="text" name="<?php echo $this->plugin->name; ?>[license_key]" value="<?php echo $screen['data']['license_key']; ?>" class="widefat"<?php echo ( $screen['data']['license_key_is_constant'] ? ' disabled="disabled"' : '' ); ?> />
							    </div>

							    <div class="option">
							    	<input type="submit" value="<?php _e( 'Save License Key', $this->plugin->name ); ?>" class="button button-primary" />

							    	<?php
							    	// Don't display the Buy a License button if a valid license key exists
							    	if ( ! $screen['data']['license_key_valid'] ) {
							    		?>
								    	<a href="<?php echo $this->plugin->purchase_url; ?>" class="button" target="_blank">
								    		<?php _e( 'Buy a License', $this->plugin->name ); ?>
								    	</a>
								    	<?php
								    }
								    ?>
							    </div>
						    </form>
					    </div>
				    </div>

				    <!-- Addons -->
				    <div id="available-addons">
				    	<h3><?php _e( 'Available Addons', $this->plugin->name ); ?></h3>
				    	<?php
				    	if ( ! $screen['data']['addons'] ) {
				    		?>
				    		<p class="description">
						    	<?php echo sprintf( __( 'We were unable to fetch the list of Addons for %s. Please reload this page to try again.', $this->plugin->name ), $this->plugin->displayName ); ?>
						    </p>
				    		<?php
				    	} else {
				    		?>
				    		<div id="the-list">
					    		<?php
					    		foreach ( $screen['data']['addons'] as $addon ) {
					    			?>
					    			<div class="plugin-card plugin-<?php echo $addon->attributes->name . ' ' . ( $addon->attributes->active ? 'active' : 'inactive' ); ?>">
					    				<div class="plugin-card-top">
					    					<div class="name column-name">
					    						<h3>
					    							<?php 
					    							echo $addon->post_title;

					    							if ( ! empty( $addon->attributes->image ) ) {
					    								?>
					    								<img src="<?php echo $addon->attributes->image[0]; ?>" class="plugin-icon" />
					    								<?php
					    							} 
													?>
					    						</h3>
					    					</div>
					    					<div class="action-links">
					    						<ul class="plugin-action-buttons">
					    							<?php
					    							// Only show actions if this Addon is licensed, and the license has not expired.
					    							if ( $addon->attributes->licensed && ! $addon->attributes->license_expired ) {
					    								// Installed?
					    								if ( ! $addon->attributes->installed ) {
					    									// Install
					    									?>
					    									<li>
							    								<a href="<?php echo $addon->attributes->install_url; ?>" class="button"><?php _e( 'Install Now' ); ?></a>
							    							</li>
							    							<?php
					    								} else {
					    									// Active?
					    									if ( ! $addon->attributes->active ) {
					    										// Activate
						    									?>
						    									<li>
								    								<a href="<?php echo $addon->attributes->activate_url; ?>" class="button button-primary"><?php _e( 'Activate' ); ?></a>
								    							</li>
								    							<?php
								    						} else {
								    							// Deactivate
								    							?>
						    									<li>
								    								<a href="<?php echo $addon->attributes->deactivate_url; ?>" class="button button-primary"><?php _e( 'Deactivate' ); ?></a>
								    							</li>
								    							<?php
								    						}
					    								}
													}
					    							?>
					    							
					    							<li>
					    								<a href="<?php echo $addon->guid; ?>" title="<?php _e( 'More Details', $this->plugin->name ); ?>" target="_blank">
					    									<?php _e( 'More Details', $this->plugin->name ); ?>
					    								</a>
					    							</li>
					    						</ul>
					    					</div>
					    					<div class="desc column-description">
					    						<?php echo $addon->post_excerpt; ?>
					    					</div>
					    				</div>

					    				<div class="plugin-card-bottom">
					    					<div class="vers column-rating">
					    						<?php echo sprintf( __( 'Version %s', $this->plugin->name ), $addon->attributes->version ); ?>
					    					</div>
					    					<div class="column-compatibility">
					    						<?php 
					    						if ( $addon->attributes->licensed ) {
					    							_e( 'Included with your License', $this->plugin->name );
					    						} else {
					    							// License key is either empty, invalid, expired or the wrong type
					    							// If the Addon doesn't have a purchase URL (which is built based on the license key if supplied),
					    							// there's nothing to action here, so don't show a button.
					    							if ( ! empty( $addon->attributes->purchase_url ) ) {
						    							if ( $screen['data']['license_key_valid'] ) {
						    								// Upgrade
						    								$label = sprintf( 'Upgrade to %s License', $addon->attributes->minimum_license_type->post_title );
						    							} else {
						    								// Purchase
						    								$label = sprintf( 'Purchase %s License', $addon->attributes->minimum_license_type->post_title );
						    							}
						    							?>
						    							<a href="<?php echo $addon->attributes->purchase_url; ?>" class="button" target="_blank">
						    								<?php
						    								echo $label;
						    								?>
						    							</a>
						    							<?php
						    						}
					    						}
					    						?>
					    					</div>
					    				</div>
					    			</div>
					    			<?php
					    		}
					    		?>
					    	</div>
					    	<?php
						}
						?>
				    </div>
				</div>
				<!-- /normal-sortables -->
    		</div>
    		<!-- /post-body-content -->
    	</div>
	</div> 
</div>
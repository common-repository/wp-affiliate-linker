<div class="wrap">
    <h2>
    	<span class="dashicons dashicons-admin-links"></span>
    	<?php echo $this->base->plugin->displayName; ?> 
    	&raquo; 
    	<?php echo $screen['label']; ?>
    </h2>

    <?php
    // Notices
    foreach ( $this->notices as $type => $notices_type ) {
    	if ( count( $notices_type ) == 0 ) {
    		continue;
    	}
    	?>
    	<div class="<?php echo ( ( $type == 'success' ) ? 'updated' : $type ); ?> notice is-dismissible">
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

    // Description
    if ( isset( $screen['description'] ) && ! empty( $screen['description'] ) ) {
    	?>
    	<p class="description">
    		<?php echo $screen['description']; ?>
	    </p>
    	<?php
    }
    ?>

    <!-- Tabs -->
	<h2 class="nav-tab-wrapper needs-js" data-panel="panel" data-form="#<?php echo $this->base->plugin->name; ?>">
		<?php
		// Iterate through this screen's tabs
		foreach ( (array) $tabs as $tab_item ) {
			// Check if an icon needs to be displayed
			$icon = '';
    		if ( ! empty( $tab_item['menu_icon'] ) ) {
    			$icon = 'dashicons ' . $tab_item['menu_icon'];
    		}
    		?>
    		<a href="#<?php echo $tab_item['name']; ?>" id="<?php echo $tab_item['name']; ?>" class="nav-tab<?php echo ( $tab_item['name'] == $tab['name'] ? ' nav-tab-active' : '' ); ?>"<?php echo ( isset( $tab_item['documentation'] ) ? ' data-documentation="' . $tab_item['documentation'] . '"' : '' ); ?>>
    			<?php
    			if ( ! empty( $icon ) ) {
    				?>
    				<span class="<?php echo $icon; ?>"></span>
    				<?php
    			}
    			
    			echo $tab_item['label'];
    			?>
    		</a>
			<?php
		}

		// Add a Documentation Tab, if a Documentation link exists
		if ( isset( $screen['documentation'] ) && ! empty( $screen['documentation'] ) ) {
			?>
			<a href="<?php echo $screen['documentation']; ?>" class="nav-tab last documentation" target="_blank">
    			<?php _e( 'Documentation', 'wp-affiliate-linker' ); ?>
    			<span class="dashicons dashicons-admin-page"></span>
    		</a>
			<?php
		}
		?>
	</h2>
	  
    <div id="poststuff">
    	<div id="post-body" class="metabox-holder columns-<?php echo $screen['columns']; ?>">
    		<!-- Content -->
    		<div id="post-body-content">
	            <div id="normal-sortables" class="meta-box-sortables ui-sortable publishing-defaults">  
	            	<form name="post" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="<?php echo $this->base->plugin->name; ?>" enctype="multipart/form-data">
		            	<?php
		            	// Load sub view
		            	require_once( $screen['view'] );
		            	
		            	// Load nonce field
		            	wp_nonce_field( $this->base->plugin->name . '_' . $screen['name'], $this->base->plugin->name . '_nonce' ); 
		            	?>
	            	</form>
				</div>
				<!-- /normal-sortables -->
    		</div>
    		<!-- /post-body-content -->

    		<!-- Sidebar -->
    		<?php
        	if ( $screen['name'] == 'settings' ) {
        		?>
        		<div id="postbox-container-1" class="postbox-container">
	        		<div class="postbox">
				        <h3 class="hndle"><?php _e( 'Notifications', 'wp-affiliate-linker' ); ?></h3>

				        <div class="option">
				            <p class="description">
				            	<?php _e('Subscribe to our newsletter and receive updates on WP Affiliate Linker', 'wp-affiliate-linker' ); ?>
				            </p>
				        </div>

				        <form action="https://n7studios.createsend.com/t/r/s/kudrldt/" method="post" id="subForm" target="_blank">
				        	<div class="option">
				        		<div class="left">
				        			<strong><?php _e( 'Your Name', 'wp-affiliate-linker' ); ?></strong>
				        		</div>
				        		<div class="right">
				        			<input id="fieldName" name="cm-name" type="text" class="widefat" />
				        		</div>
				        	</div>

				        	<div class="option">
				        		<div class="left">
				        			<strong><?php _e( 'Your Email', 'wp-affiliate-linker' ); ?></strong>
				        		</div>
				        		<div class="right">
				        			<input id="fieldEmail" name="cm-kudrldt-kudrldt" type="email" class="widefat" required />
				        		</div>
				        	</div>

				        	<div class="option">
				        		<input type="submit" value="<?php _e( 'Subscribe', 'wp-affiliate-linker' ); ?>" class="button button-primary" />
				        	</div>
				        </form>
				    </div>
			    </div>
				<?php
        	}
        	?>
    	</div>
	</div> 
	<!-- /poststuff -->         
</div>
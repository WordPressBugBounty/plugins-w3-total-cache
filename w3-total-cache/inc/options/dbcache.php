<?php
namespace W3TC;

defined( 'W3TC' ) || die();

?>
<?php require W3TC_INC_DIR . '/options/common/header.php'; ?>

<p>
	<?php
	echo wp_kses(
		sprintf(
			// translators: 1 Database cache engine name, 2 HTML span indicating DB cache enabled/disabled.
			__(
				'Database caching via %1$s is currently %2$s.',
				'w3-total-cache'
			),
			esc_html( Cache::engine_name( $this->_config->get_string( 'dbcache.engine' ) ) ),
			'<span class="w3tc-' . ( $dbcache_enabled ? 'enabled">' . esc_html__( 'enabled', 'w3-total-cache' ) : 'disabled">' . esc_html__( 'disabled', 'w3-total-cache' ) ) . '</span>'
		),
		array(
			'span' => array(
				'class' => array(),
			),
		)
	);
	?>
</p>

<form action="admin.php?page=<?php echo esc_attr( $this->_page ); ?>" method="post">
	<?php Util_UI::print_control_bar( 'dbcache_form_control' ); ?>
	<div class="metabox-holder">
		<?php Util_Ui::postbox_header( esc_html__( 'General', 'w3-total-cache' ), '', 'general' ); ?>
		<table class="form-table">
			<tr>
				<th>
					<?php $this->checkbox( 'dbcache.reject.logged' ); ?> <?php Util_Ui::e_config_label( 'dbcache.reject.logged' ); ?></label>
					<p class="description"><?php esc_html_e( 'Enabling this option is recommended to maintain default WordPress behavior.', 'w3-total-cache' ); ?></p>
				</th>
			</tr>
		</table>

		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( esc_html__( 'Advanced', 'w3-total-cache' ), '', 'advanced' ); ?>
		<table class="form-table">
			<?php
			if ( 'memcached' === $this->_config->get_string( 'dbcache.engine' ) ) {
				$module = 'dbcache';
				include W3TC_INC_DIR . '/options/parts/memcached.php';
			} elseif ( 'redis' === $this->_config->get_string( 'dbcache.engine' ) ) {
				$module = 'dbcache';
				include W3TC_INC_DIR . '/options/parts/redis.php';
			}
			?>
			<tr>
				<th style="width: 250px;"><label for="dbcache_lifetime"><?php Util_Ui::e_config_label( 'dbcache.lifetime' ); ?></label></th>
				<td>
					<input id="dbcache_lifetime" type="text" name="dbcache__lifetime"
						<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?>
						value="<?php echo esc_attr( $this->_config->get_integer( 'dbcache.lifetime' ) ); ?>" size="8" /> <?php esc_html_e( 'seconds', 'w3-total-cache' ); ?>
					<p class="description"><?php esc_html_e( 'Determines the natural expiration time of unchanged cache items. The higher the value, the larger the cache.', 'w3-total-cache' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dbcache_file_gc"><?php Util_Ui::e_config_label( 'dbcache.file.gc' ); ?></label></th>
				<td>
					<input id="dbcache_file_gc" type="text" name="dbcache__file__gc"
					<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?> value="<?php echo esc_attr( $this->_config->get_integer( 'dbcache.file.gc' ) ); ?>" size="8" /> <?php esc_html_e( 'seconds', 'w3-total-cache' ); ?>
					<p class="description"><?php esc_html_e( 'If caching to disk, specify how frequently expired cache data is removed. For busy sites, a lower value is best.', 'w3-total-cache' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dbcache_reject_uri"><?php Util_Ui::e_config_label( 'dbcache.reject.uri' ); ?></label></th>
				<td>
					<textarea id="dbcache_reject_uri" name="dbcache__reject__uri"
						<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?> cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'dbcache.reject.uri' ) ) ); ?></textarea>
						<p class="description">
							<?php
							echo wp_kses(
								sprintf(
									// translators: 1 opening HTML a tag to W3TC regex support, 2 opening HTML acronym tag,
									// translators: 3 closing HTML acronym tag, 4 closing HTML a tag.
									__(
										'Always ignore the specified pages / directories. Supports regular expressions (See %1$s%2$sFAQ%3$s%4$s).',
										'w3-total-cache'
									),
									'<a href="' . esc_url( 'https://api.w3-edge.com/v1/redirects/faq/usage/regexp-support' ) . '">',
									'<acronym title="' . esc_attr__( 'Frequently Asked Questions', 'w3-total-cache' ) . '">',
									'</acronym>',
									'</a>'
								),
								array(
									'a'       => array(
										'href' => array(),
									),
									'acronym' => array(
										'title' => array(),
									),
								)
							);
							?>
						</p>
				</td>
			</tr>
			<tr>
				<th><label for="dbcache_reject_sql"><?php Util_Ui::e_config_label( 'dbcache.reject.sql' ); ?></label></th>
				<td>
					<textarea id="dbcache_reject_sql" name="dbcache__reject__sql"
						<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?> cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'dbcache.reject.sql' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Do not cache queries that contain these terms. Any entered prefix (set in wp-config.php) will be replaced with current database prefix (default: wp_). Query stems can be identified using debug mode.', 'w3-total-cache' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dbcache_reject_words"><?php Util_Ui::e_config_label( 'dbcache.reject.words' ); ?></label></th>
				<td>
					<textarea id="dbcache_reject_words" name="dbcache__reject__words"
						<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?> cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'dbcache.reject.words' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Do not cache queries that contain these words or regular expressions.', 'w3-total-cache' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dbcache_reject_constants"><?php esc_html_e( 'Reject constants:' ); ?></label></th>
				<td>
					<textarea id="dbcache_reject_constants" name="dbcache__reject__constants"
						<?php Util_Ui::sealing_disabled( 'dbcache.' ); ?> cols="40" rows="5"><?php echo esc_textarea( implode( "\r\n", $this->_config->get_array( 'dbcache.reject.constants' ) ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Disable caching once specified constants defined.', 'w3-total-cache' ); ?></p>
				</td>
			</tr>
			<?php
			Util_Ui::config_item(
				array(
					'key'            => 'dbcache.wpcli_disk',
					'label'          => esc_html__( 'Enable for WP-CLI', 'w3-total-cache' ),
					'checkbox_label' => esc_html__( 'Enable', 'w3-total-cache' ),
					'control'        => 'checkbox',
					'disabled'       => ! $dbcache_enabled,
				)
			);
			?>
		</table>

		<?php Util_Ui::postbox_footer(); ?>

		<?php Util_Ui::postbox_header( esc_html__( 'Purge via WP Cron', 'w3-total-cache' ), '', 'dbcache_wp_cron' ); ?>
		<table class="form-table">
			<p>
				<?php
				echo wp_kses(
					sprintf(
						// Translators: 1 opening HTML a tag, 2 closing HTML a tag.
						__(
							'Enabling this will schedule a WP-Cron event that will flush the Database Cache. If you prefer to use a system cron job instead of WP-Cron, you can schedule the following command to run at your desired interval: "wp w3tc flush db". Visit %1$shere%2$s for more information.',
							'w3-total-cache'
						),
						'<a href="' . esc_url( 'https://www.boldgrid.com/support/w3-total-cache/schedule-cache-purges/' ) . '" target="_blank">',
						'</a>'
					),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
			</p>
			<?php
			$wp_disabled = ! $this->_config->get_boolean( 'dbcache.wp_cron' );

			if ( ! $dbcache_enabled ) {
				echo wp_kses(
					sprintf(
						// Translators: 1 opening HTML div tag followed by opening HTML p tag, 2 opening HTML a tag,
						// Translators: 3 closing HTML a tag, 4 closing HTML p tag followed by closing HTML div tag.
						__( '%1$sDatabase Cache is disabled! Enable it %2$shere%3$s to enable this feature.%4$s', 'w3-total-cache' ),
						'<div class="notice notice-error inline"><p>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=w3tc_general#database_cache' ) ) . '">',
						'</a>',
						'</p></div>'
					),
					array(
						'div' => array(
							'class' => array(),
						),
						'p'   => array(),
						'a'   => array(
							'href' => array(),
						),
					)
				);
			}

			Util_Ui::config_item(
				array(
					'key'            => 'dbcache.wp_cron',
					'label'          => esc_html__( 'Enable WP-Cron Event', 'w3-total-cache' ),
					'checkbox_label' => esc_html__( 'Enable', 'w3-total-cache' ),
					'control'        => 'checkbox',
					'disabled'       => ! $dbcache_enabled,
				)
			);

			$time_options = array();
			for ( $hour = 0; $hour < 24; $hour++ ) {
				foreach ( array( '00', '30' ) as $minute ) {
					$time_value                  = $hour * 60 + intval( $minute );
					$scheduled_time              = new \DateTime( "{$hour}:{$minute}", wp_timezone() );
					$time_label                  = $scheduled_time->format( 'g:i a' );
					$time_options[ $time_value ] = $time_label;
				}
			}

			Util_Ui::config_item(
				array(
					'key'              => 'dbcache.wp_cron_time',
					'label'            => esc_html__( 'Start Time', 'w3-total-cache' ),
					'control'          => 'selectbox',
					'selectbox_values' => $time_options,
					'description'      => esc_html__( 'This setting controls the initial start time of the cron job. If the selected time has already passed, it will schedule the job for the following day at the selected time.', 'w3-total-cache' ),
					'disabled'         => ! $dbcache_enabled || $wp_disabled,
				)
			);

			Util_Ui::config_item(
				array(
					'key'              => 'dbcache.wp_cron_interval',
					'label'            => esc_html__( 'Interval', 'w3-total-cache' ),
					'control'          => 'selectbox',
					'selectbox_values' => array(
						'hourly'     => esc_html__( 'Hourly', 'w3-total-cache' ),
						'twicedaily' => esc_html__( 'Twice Daily', 'w3-total-cache' ),
						'daily'      => esc_html__( 'Daily', 'w3-total-cache' ),
						'weekly'     => esc_html__( 'Weekly', 'w3-total-cache' ),
					),
					'description'      => esc_html__( 'This setting controls the interval that the cron job should occur.', 'w3-total-cache' ),
					'disabled'         => ! $dbcache_enabled || $wp_disabled,
				)
			);
			?>
		</table>
		<?php Util_Ui::postbox_footer(); ?>
	</div>
</form>

<?php
/**
 * File: Generic_AdminActions_Default.php
 *
 * @package W3TC
 */

namespace W3TC;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

define( 'W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN', '~define\s*\(\s*[\'"]COOKIE_DOMAIN[\'"]\s*,.*?\)~is' );

/**
 * Class Generic_AdminActions_Default
 *
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 * phpcs:disable WordPress.NamingConventions.ValidHookName.UseUnderscores
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 * phpcs:disable WordPress.WP.AlternativeFunctions
 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
 */
class Generic_AdminActions_Default {
	/**
	 * Config
	 *
	 * @var Config
	 */
	private $_config = null;

	/**
	 * Config master
	 *
	 * @var Config
	 */
	private $_config_master = null;

	/**
	 * Current page
	 *
	 * @var null|string
	 */
	private $_page = null;

	/**
	 * Initializes the class instance and loads configuration settings.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->_config        = Dispatcher::config();
		$this->_config_master = Dispatcher::config_master();

		$this->_page = Util_Admin::get_current_page();
	}

	/**
	 * Enables preview mode and redirects to the home URL.
	 *
	 * @return void
	 */
	public function w3tc_default_previewing() {
		Util_Environment::set_preview( true );
		Util_Environment::redirect( get_home_url() );
	}

	/**
	 * Disables preview mode and redirects to the current page.
	 *
	 * @return void
	 */
	public function w3tc_default_stop_previewing() {
		Util_Environment::set_preview( false );
		Util_Admin::redirect( array(), true );
	}

	/**
	 * Saves the provided license key to the configuration.
	 *
	 * @return void
	 *
	 * @throws \Exception If saving the license key or configuration fails.
	 */
	public function w3tc_default_save_license_key() {
		$license = Util_Request::get_string( 'license_key' );
		try {
			$old_config = new Config();

			$this->_config->set( 'plugin.license_key', $license );
			$this->_config->save();

			Dispatcher::component( 'Licensing_Plugin_Admin' )->possible_state_change(
				$this->_config,
				$old_config
			);
		} catch ( \Exception $ex ) {
			echo wp_json_encode( array( 'result' => 'failed' ) );
			exit();
		}

		echo wp_json_encode( array( 'result' => 'success' ) );
		exit();
	}

	/**
	 * Hides a specified admin note and updates the configuration.
	 *
	 * @return void
	 */
	public function w3tc_default_hide_note() {
		$note    = Util_Request::get_string( 'note' );
		$setting = sprintf( 'notes.%s', $note );

		$this->_config->set( $setting, false );
		$this->_config->save();

		do_action( "w3tc_hide_button-{$note}" );
		Util_Admin::redirect( array(), true );
	}

	/**
	 * Updates a specified configuration state value and saves the changes.
	 *
	 * @return void
	 */
	public function w3tc_default_config_state() {
		$key   = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$config_state = Dispatcher::config_state_master();
		$config_state->set( $key, $value );
		$config_state->save();
		Util_Admin::redirect( array(), true );
	}

	/**
	 * Updates a specified master configuration state value and saves the changes.
	 *
	 * @return void
	 */
	public function w3tc_default_config_state_master() {
		$key   = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$config_state = Dispatcher::config_state_master();
		$config_state->set( $key, $value );
		$config_state->save();

		Util_Admin::redirect( array(), true );
	}

	/**
	 * Updates a specified note configuration state and redirects.
	 *
	 * @return void
	 */
	public function w3tc_default_config_state_note() {
		$key   = Util_Request::get_string( 'key' );
		$value = Util_Request::get_string( 'value' );

		$s = Dispatcher::config_state_note();
		$s->set( $key, $value );

		Util_Admin::redirect( array(), true );
	}

	/**
	 * Hides a custom admin note and redirects.
	 *
	 * @return void
	 */
	public function w3tc_default_hide_note_custom() {
		$note = Util_Request::get_string( 'note' );
		do_action( "w3tc_hide_button_custom-{$note}" );
		Util_Admin::redirect( array(), true );
	}

	/**
	 * Clears the purge log for the specified module.
	 *
	 * @return void
	 */
	public function w3tc_default_purgelog_clear() {
		$module       = Util_Request::get_label( 'module' );
		$log_filename = Util_Debug::log_filename( $module . '-purge' );

		if ( file_exists( $log_filename ) ) {
			unlink( $log_filename );
		}

		Util_Admin::redirect(
			array(
				'page'   => 'w3tc_general',
				'view'   => 'purge_log',
				'module' => $module,
			),
			true
		);
	}

	/**
	 * Removes an add-in module, handles deletion, and performs necessary replacements.
	 *
	 * @return void
	 */
	public function w3tc_default_remove_add_in() {
		$module = Util_Request::get_string( 'w3tc_default_remove_add_in' );

		// in the case of missing permissions to delete
		// environment will use that to try to override addin via ftp.
		set_transient( 'w3tc_remove_add_in_' . $module, 'yes', 600 );

		switch ( $module ) {
			case 'pgcache':
				Util_WpFile::delete_file( W3TC_ADDIN_FILE_ADVANCED_CACHE );
				$src = W3TC_INSTALL_FILE_ADVANCED_CACHE;
				$dst = W3TC_ADDIN_FILE_ADVANCED_CACHE;
				try {
					Util_WpFile::copy_file( $src, $dst );
				} catch ( Util_WpFile_FilesystemOperationException $ex ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// missing exception handle?
				}
				break;
			case 'dbcache':
				Util_WpFile::delete_file( W3TC_ADDIN_FILE_DB );
				break;
			case 'objectcache':
				Util_WpFile::delete_file( W3TC_ADDIN_FILE_OBJECT_CACHE );
				break;
		}
		Util_Admin::redirect(
			array(
				'w3tc_note' => 'add_in_removed',
			),
			true
		);
	}

	/**
	 * Saves configuration options and processes the save request.
	 *
	 * @return void
	 */
	public function w3tc_save_options() {
		$redirect_data = $this->_w3tc_save_options_process();
		Util_Admin::redirect_with_custom_messages2( $redirect_data );
	}

	/**
	 * Saves configuration options, flushes caches, and updates necessary states.
	 *
	 * @return void
	 */
	public function w3tc_default_save_and_flush() {
		$redirect_data = $this->_w3tc_save_options_process();

		$f = Dispatcher::component( 'CacheFlush' );
		$f->flush_all();

		$state_note = Dispatcher::config_state_note();
		$state_note->set( 'common.show_note.flush_statics_needed', false );
		$state_note->set( 'common.show_note.flush_posts_needed', false );
		$state_note->set( 'common.show_note.plugins_updated', false );
		$state_note->set( 'minify.show_note.need_flush', false );
		$state_note->set( 'objectcache.show_note.flush_needed', false );

		Util_Admin::redirect_with_custom_messages2( $redirect_data );
	}

	/**
	 * Processes saving options for the W3 Total Cache plugin.
	 *
	 * @return array
	 */
	private function _w3tc_save_options_process() {
		$data = array(
			'old_config'            => $this->_config,
			'response_query_string' => array(),
			'response_actions'      => array(),
			'response_errors'       => array(),
			'response_notes'        => array( 'config_save' ),
		);

		// if we are on extension settings page - stay on the same page.
		if ( 'w3tc_extensions' === Util_Request::get_string( 'page' ) ) {
			$data['response_query_string']['page']      = Util_Request::get_string( 'page' );
			$data['response_query_string']['extension'] = Util_Request::get_string( 'extension' );
			$data['response_query_string']['action']    = Util_Request::get_string( 'action' );
		}

		$capability = apply_filters( 'w3tc_capability_config_save', 'manage_options' );
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have the rights to perform this action.', 'w3-total-cache' ) );
		}

		/**
		 * Read config
		 * We should use new instance of WP_Config object here
		 */
		$config = new Config();
		$this->read_request( $config );

		/**
		 * General tab
		 */
		if ( 'w3tc_general' === $this->_page ) {
			$file_nfs     = Util_Request::get_boolean( 'file_nfs' );
			$file_locking = Util_Request::get_boolean( 'file_locking' );

			$config->set( 'pgcache.file.nfs', $file_nfs );
			$config->set( 'minify.file.nfs', $file_nfs );

			$config->set( 'dbcache.file.locking', $file_locking );
			$config->set( 'objectcache.file.locking', $file_locking );
			$config->set( 'pgcache.file.locking', $file_locking );
			$config->set( 'minify.file.locking', $file_locking );

			if ( is_network_admin() ) {
				if ( ( $this->_config->get_boolean( 'common.force_master' ) !== $config->get_boolean( 'common.force_master' ) ) ) {
					// blogmap is wrong so empty it.
					@unlink( W3TC_CACHE_BLOGMAP_FILENAME );
					$blogmap_dir = dirname( W3TC_CACHE_BLOGMAP_FILENAME ) . '/' . basename( W3TC_CACHE_BLOGMAP_FILENAME, '.php' ) . '/';
					if ( @is_dir( $blogmap_dir ) ) {
						Util_File::rmdir( $blogmap_dir );
					}
				}
			}

			/**
			 * Check permalinks for page cache
			 */
			if ( $config->get_boolean( 'pgcache.enabled' ) &&
				'file_generic' === $config->get_string( 'pgcache.engine' ) &&
				! get_option( 'permalink_structure' ) ) {

				$config->set( 'pgcache.enabled', false );
				$data['response_errors'][] = 'fancy_permalinks_disabled_pgcache';
			}

			/**
			 * Check for Object Cache using Disk being disabled or changed to another engine.
			 *
			 * @since 2.8.6
			 */
			if (
				$this->_config->get_boolean( 'objectcache.enabled' ) && 'file' === $this->_config->get_string( 'objectcache.engine' ) &&
				( ! $config->get_boolean( 'objectcache.enabled' ) || 'file' !== $config->get_string( 'objectcache.engine' ) )
			) {
				Util_File::rmdir( Util_Environment::cache_blog_dir( 'object' ) );
			}

			/**
			 * Check for Image Service extension status changes.
			 */
			if ( $config->get_boolean( 'extension.imageservice' ) !== $this->_config->get_boolean( 'extension.imageservice' ) ) {
				if ( $config->get_boolean( 'extension.imageservice' ) ) {
					Extensions_Util::activate_extension( 'imageservice', $config );
				} else {
					Extensions_Util::deactivate_extension( 'imageservice', $config );
				}
			}
		}

		/**
		 * Minify tab
		 */
		if ( 'w3tc_minify' === $this->_page ) {
			if ( ( $this->_config->get_boolean( 'minify.js.http2push' ) && ! $config->get_boolean( 'minify.js.http2push' ) ) ||
			( $this->_config->get_boolean( 'minify.css.http2push' ) && ! $config->get_boolean( 'minify.css.http2push' ) ) ) {
				if ( 'file_generic' === $config->get_string( 'pgcache.engine' ) ) {
					$cache_dir = Util_Environment::cache_blog_dir( 'page_enhanced' );
					$this->_delete_all_htaccess_files( $cache_dir );
				}
			}

			if ( ! $this->_config->get_boolean( 'minify.auto' ) ) {
				$js_groups  = array();
				$css_groups = array();

				$js_files  = Util_Request::get_array( 'js_files' );
				$css_files = Util_Request::get_array( 'css_files' );

				foreach ( $js_files as $theme => $templates ) {
					foreach ( $templates as $template => $locations ) {
						foreach ( (array) $locations as $location => $types ) {
							foreach ( (array) $types as $files ) {
								foreach ( (array) $files as $file ) {
									if ( ! empty( $file ) ) {
										$js_groups[ $theme ][ $template ][ $location ]['files'][] =
											Util_Environment::normalize_file_minify( $file );
									}
								}
							}
						}
					}
				}

				foreach ( $css_files as $theme => $templates ) {
					foreach ( $templates as $template => $locations ) {
						foreach ( (array) $locations as $location => $files ) {
							foreach ( (array) $files as $file ) {
								if ( ! empty( $file ) ) {
									$css_groups[ $theme ][ $template ][ $location ]['files'][] =
										Util_Environment::normalize_file_minify( $file );
								}
							}
						}
					}
				}

				$config->set( 'minify.js.groups', $js_groups );
				$config->set( 'minify.css.groups', $css_groups );

				$js_theme  = Util_Request::get_string( 'js_theme' );
				$css_theme = Util_Request::get_string( 'css_theme' );

				$data['response_query_string']['js_theme']  = $js_theme;
				$data['response_query_string']['css_theme'] = $css_theme;
			}
		}

		/**
		 * Browser Cache tab
		 */
		if ( 'w3tc_browsercache' === $this->_page ) {
			if ( $config->get_boolean( 'browsercache.enabled' ) &&
				$config->get_boolean( 'browsercache.no404wp' ) &&
				! get_option( 'permalink_structure' ) ) {

				$config->set( 'browsercache.no404wp', false );
				$data['response_errors'][] = 'fancy_permalinks_disabled_browsercache';
			}
		}

		/**
		 * CDN tab
		 */
		if ( 'w3tc_cdn' === $this->_page ) {
			$cdn_cnames  = Util_Request::get_array( 'cdn_cnames' );
			$cdn_domains = array();

			foreach ( $cdn_cnames as $cdn_cname ) {
				$cdn_cname = preg_replace( '~[^0-9a-zA-Z/_.:\-]~', '', wp_strip_all_tags( $cdn_cname ) );

				/**
				 * Auto expand wildcard domain to 10 subdomains
				 */
				$matches = null;

				if ( preg_match( '~^\*\.(.*)$~', $cdn_cname, $matches ) ) {
					$cdn_domains = array();

					for ( $i = 1; $i <= 10; $i++ ) {
						$cdn_domains[] = sprintf( 'cdn%d.%s', $i, $matches[1] );
					}

					break;
				}

				if ( $cdn_cname ) {
					$cdn_domains[] = $cdn_cname;
				}
			}

			switch ( $this->_config->get_string( 'cdn.engine' ) ) {
				case 'akamai':
					$config->set( 'cdn.akamai.domain', $cdn_domains );
					break;

				case 'att':
					$config->set( 'cdn.att.domain', $cdn_domains );
					break;

				case 'azure':
					$config->set( 'cdn.azure.cname', $cdn_domains );
					break;

				case 'azuremi':
					$config->set( 'cdn.azuremi.cname', $cdn_domains );
					break;

				case 'cf':
					$config->set( 'cdn.cf.cname', $cdn_domains );
					break;

				case 'cf2':
					$config->set( 'cdn.cf2.cname', $cdn_domains );
					break;

				case 'cotendo':
					$config->set( 'cdn.cotendo.domain', $cdn_domains );
					break;

				case 'edgecast':
					$config->set( 'cdn.edgecast.domain', $cdn_domains );
					break;

				case 'ftp':
					$config->set( 'cdn.ftp.domain', $cdn_domains );
					break;

				case 'mirror':
					$config->set( 'cdn.mirror.domain', $cdn_domains );
					break;

				case 'rackspace_cdn':
					$config->set( 'cdn.rackspace_cdn.domains', $cdn_domains );
					break;

				case 'rscf':
					$config->set( 'cdn.rscf.cname', $cdn_domains );
					break;

				case 's3':
				case 's3_compatible':
					$config->set( 'cdn.s3.cname', $cdn_domains );
					break;
			}
		}

		$old_ext_settings = $this->_config->get_array( 'extensions.settings', array() );
		$new_ext_settings = $old_ext_settings;
		$modified         = false;

		$extensions = Extensions_Util::get_extensions( $config );
		foreach ( $extensions as $extension => $descriptor ) {
			$request = Util_Request::get_as_array( 'extensions.settings.' . $extension . '.' );
			if ( count( $request ) > 0 ) {
				if ( ! isset( $new_ext_settings[ $extension ] ) ) {
					$new_ext_settings[ $extension ] = array();
				}

				foreach ( $request as $key => $value ) {
					if ( ! isset( $old_ext_settings[ $extension ] ) ||
						! isset( $old_ext_settings[ $extension ][ $key ] ) ||
						$old_ext_settings[ $extension ][ $key ] !== $value ) {

						$new_ext_settings[ $extension ][ $key ] = $value;
						$modified                               = true;
					}
				}
			}
		}

		if ( $modified ) {
			$config->set( 'extensions.settings', $new_ext_settings );
		}

		$data['new_config'] = $config;
		$data               = apply_filters( 'w3tc_save_options', $data, $this->_page );
		$config             = $data['new_config'];

		do_action( 'w3tc_config_ui_save', $config, $this->_config );
		do_action( "w3tc_config_ui_save-{$this->_page}", $config, $this->_config );

		Util_Admin::config_save( $this->_config, $config );

		if ( 'w3tc_cdn' === $this->_page ) {
			/**
			 * Handle Set Cookie Domain
			 */
			$set_cookie_domain_old = Util_Request::get_boolean( 'set_cookie_domain_old' );
			$set_cookie_domain_new = Util_Request::get_boolean( 'set_cookie_domain_new' );

			if ( $set_cookie_domain_old !== $set_cookie_domain_new ) {
				if ( $set_cookie_domain_new ) {
					if ( ! $this->enable_cookie_domain() ) {
						Util_Admin::redirect(
							array_merge(
								$data['response_query_string'],
								array(
									'w3tc_error' => 'enable_cookie_domain',
								)
							)
						);
					}
				} elseif ( ! $this->disable_cookie_domain() ) {
					Util_Admin::redirect(
						array_merge(
							$data['response_query_string'],
							array(
								'w3tc_error' => 'disable_cookie_domain',
							)
						)
					);
				}
			}
		}

		return array(
			'query_string' => $data['response_query_string'],
			'actions'      => $data['response_actions'],
			'errors'       => $data['response_errors'],
			'notes'        => $data['response_notes'],
		);
	}

	/**
	 * Deletes all .htaccess files in the specified directory and its subdirectories.
	 *
	 * @param string $dir Directory path where .htaccess files will be deleted.
	 *
	 * @return void
	 */
	private function _delete_all_htaccess_files( $dir ) {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		$handle = opendir( $dir );
		if ( false === $handle ) {
			return;
		}

		while ( true ) {
			$file = readdir( $handle );
			if ( false === $file ) {
				break;
			}

			if ( '.' === $file || '..' === $file ) {
				continue;
			}

			if ( is_dir( $file ) ) {
				$this->_delete_all_htaccess_files( $file );
				continue;
			} elseif ( '.htaccess' === $file ) {
				@unlink( $dir . '/' . $file );
			}
		}

		closedir( $handle );
	}

	/**
	 * Enables COOKIE_DOMAIN by modifying the wp-config.php file.
	 *
	 * @return bool True if COOKIE_DOMAIN is successfully enabled, false otherwise.
	 */
	public function enable_cookie_domain() {
		WP_Filesystem();

		global $wp_filesystem;

		$config_path = Util_Environment::wp_config_path();
		$config_data = $wp_filesystem->get_contents( $config_path );

		if ( false === $config_data ) {
			return false;
		}

		$cookie_domain = Util_Admin::get_cookie_domain();

		if ( $this->is_cookie_domain_define( $config_data ) ) {
			$new_config_data = preg_replace(
				W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN,
				"define('COOKIE_DOMAIN', '" . addslashes( $cookie_domain ) . "')",
				$config_data,
				1
			);
		} else {
			$new_config_data = preg_replace(
				'~<\?(php)?~',
				"\\0\r\ndefine('COOKIE_DOMAIN', '" . addslashes( $cookie_domain ) .
					"'); // " . __( 'Added by W3 Total Cache', 'w3-total-cache' ) . "\r\n",
				$config_data,
				1
			);
		}

		if ( $new_config_data !== $config_data ) {
			if ( ! $wp_filesystem->put_contents( $config_path, $new_config_data ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Disables COOKIE_DOMAIN by modifying the wp-config.php file.
	 *
	 * @return bool True if COOKIE_DOMAIN is successfully disabled, false otherwise.
	 */
	public function disable_cookie_domain() {
		WP_Filesystem();

		global $wp_filesystem;

		$config_path = Util_Environment::wp_config_path();
		$config_data = $wp_filesystem->get_contents( $config_path );

		if ( false === $config_data ) {
			return false;
		}

		if ( $this->is_cookie_domain_define( $config_data ) ) {
			$new_config_data = preg_replace(
				W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN,
				"define('COOKIE_DOMAIN', false)",
				$config_data,
				1
			);

			if ( $new_config_data !== $config_data ) {
				if ( ! $wp_filesystem->put_contents( $config_path, $new_config_data ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Checks if COOKIE_DOMAIN is defined in the given configuration content.
	 *
	 * @param string $content The configuration file content to check.
	 *
	 * @return int|bool True if COOKIE_DOMAIN is defined, false otherwise.
	 */
	public function is_cookie_domain_define( $content ) {
		return preg_match( W3TC_PLUGIN_TOTALCACHE_REGEXP_COOKIEDOMAIN, $content );
	}

	/**
	 * Checks if a configuration section is sealed.
	 *
	 * @param string $section The section name to check.
	 *
	 * @return bool Always returns true, indicating the section is sealed.
	 */
	protected function is_sealed( $section ) {
		return true;
	}

	/**
	 * Reads configuration settings from a request and updates the configuration object.
	 *
	 * @param object $config Configuration object to update.
	 *
	 * @return void
	 */
	public function read_request( $config ) {
		$request = Util_Request::get_request();

		include W3TC_DIR . '/ConfigKeys.php';   // define $keys.

		foreach ( $request as $request_key => $request_value ) {
			if ( is_array( $request_value ) ) {
				$request_value = array_map( 'stripslashes_deep', $request_value );
			} else {
				$request_value = stripslashes( $request_value );
			}

			if ( 'extension__' === substr( $request_key, 0, 11 ) ) {
				$extension_id = Util_Ui::config_key_from_http_name( substr( $request_key, 11 ) );

				if ( '1' === $request_value ) {
					Extensions_Util::activate_extension( $extension_id, $config, true );
				} else {
					Extensions_Util::deactivate_extension( $extension_id, $config, true );
				}
			}

			$key        = Util_Ui::config_key_from_http_name( $request_key );
			$descriptor = null;

			if ( ! is_array( $key ) && array_key_exists( $key, $keys ) ) {
				$descriptor = $keys[ $key ];
			}

			/**
			 * This filter is needed for compound keys to set the appropirate data type to save as.
			 * Mainly used by extensions with textarea fields that don't feature a ConfigKeys entry.
			 * If no filter exists to define such fields it will save as a string, requiring post-processing.
			 *
			 * @since 2.4.2
			 *
			 * @param mixed $descriptor Array containing correct data type or null if not matched.
			 * @param array $key        Key to match on.
			*/
			$descriptor = apply_filters( 'w3tc_config_key_descriptor', $descriptor, $key );

			if ( isset( $descriptor['type'] ) ) {
				if ( 'array' === $descriptor['type'] ) {
					if ( is_array( $request_value ) ) {
						// This is needed for radio inputs.
						$request_value = implode( "\n", $request_value );
					}
					$request_value = Util_Environment::textarea_to_array( $request_value );
				} elseif ( 'boolean' === $descriptor['type'] ) {
					$request_value = ( '1' === $request_value );
				} elseif ( 'integer' === $descriptor['type'] ) {
					$request_value = (int) $request_value;
				}
			}

			$config->set( $key, $request_value );
		}
	}
}

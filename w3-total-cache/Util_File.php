<?php
/**
 * File: Util_File.php
 *
 * @package W3TC
 */

namespace W3TC;

/**
 * Class Util_File
 *
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 * phpcs:disable WordPress.WP.AlternativeFunctions
 */
class Util_File {
	/**
	 * Recursive creates directory
	 *
	 * @param string  $path      Path.
	 * @param integer $mask      Mask.
	 * @param string  $curr_path Current Path.
	 *
	 * @return boolean
	 */
	public static function mkdir( $path, $mask = 0777, $curr_path = '' ) {
		$path = Util_Environment::realpath( $path );
		$path = trim( $path, '/' );
		$dirs = explode( '/', $path );

		foreach ( $dirs as $dir ) {
			if ( '' === $dir ) {
				return false;
			}

			$curr_path .= ( '' === $curr_path ? '' : '/' ) . $dir;

			if ( ! @file_exists( $curr_path ) ) {
				if ( ! @mkdir( $curr_path, $mask ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Recursive creates directory from some directory
	 * Does not try to create directory before from
	 *
	 * @param string  $path      Path.
	 * @param string  $from_path From path.
	 * @param integer $mask      Mask.
	 *
	 * @return boolean
	 */
	public static function mkdir_from( $path, $from_path = '', $mask = 0777 ) {
		$path = Util_Environment::realpath( $path );

		$from_path = Util_Environment::realpath( $from_path );
		if ( substr( $path, 0, strlen( $from_path ) ) !== $from_path ) {
			return false;
		}

		$path = substr( $path, strlen( $from_path ) );

		$path = trim( $path, '/' );
		$dirs = explode( '/', $path );

		$curr_path = $from_path;

		foreach ( $dirs as $dir ) {
			if ( '' === $dir ) {
				return false;
			}

			$curr_path .= ( '' === $curr_path ? '' : '/' ) . $dir;

			if ( ! @file_exists( $curr_path ) ) {
				if ( ! @mkdir( $curr_path, $mask, true ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Recursive creates directory from some directory
	 * Safely for web-accessible folders
	 * (no .htaccess folders which cause 403 error later)
	 * Does not try to create directory before from
	 *
	 * @param string  $path      Path.
	 * @param string  $from_path From path.
	 * @param integer $mask      Mask.
	 *
	 * @return boolean
	 */
	public static function mkdir_from_safe( $path, $from_path = '', $mask = 0777 ) {
		$path = Util_Environment::realpath( $path );

		$from_path = Util_Environment::realpath( $from_path );
		if ( substr( $path, 0, strlen( $from_path ) ) !== $from_path ) {
			return false;
		}

		$path = substr( $path, strlen( $from_path ) );

		$path = trim( $path, '/' );
		$dirs = explode( '/', $path );

		$curr_path          = realpath( $from_path );   // use canonicalization.
		$curr_path_previous = $curr_path;

		foreach ( $dirs as $dir ) {
			if ( '' === $dir ) {
				return false;
			}

			if ( '.' === substr( $dir, 0, 1 ) ) { // (no .htaccess folders).
				return false;
			}

			$curr_path .= ( '' === $curr_path ? '' : '/' ) . $dir;

			if ( ! @file_exists( $curr_path ) ) {
				if ( ! @mkdir( $curr_path, $mask, true ) ) {
					return false;
				}

				$curr_path = realpath( $curr_path );
				// make sure we grow from previous step and dont jump elsewhere.
				if ( strlen( $curr_path ) <= 0 ||
						substr( $curr_path, 0, strlen( $curr_path_previous ) ) !== $curr_path_previous ) {
					return false;
				}

				$curr_path_previous = $curr_path;
			}
		}

		return true;
	}

	/**
	 * Recursive remove dir
	 *
	 * @param string $path    Path.
	 * @param array  $exclude Exclude.
	 * @param bool   $remove  Remove.
	 *
	 * @return void
	 */
	public static function rmdir( $path, $exclude = array(), $remove = true ) {
		$dir = file_exists( $path ) ? opendir( $path ) : false;

		if ( $dir ) {
			$entry = @readdir( $dir );
			while ( false !== $entry ) {
				if ( '.' === $entry || '..' === $entry ) {
					$entry = @readdir( $dir );
					continue;
				}

				foreach ( $exclude as $mask ) {
					if ( fnmatch( $mask, basename( $entry ) ) ) {
						$entry = @readdir( $dir );
						continue 2;
					}
				}

				$full_path = $path . DIRECTORY_SEPARATOR . $entry;

				if ( @is_dir( $full_path ) ) {
					self::rmdir( $full_path, $exclude );
				} else {
					@unlink( $full_path );
				}

				$entry = @readdir( $dir );
			}

			@closedir( $dir );

			if ( $remove ) {
				@rmdir( $path );
			}
		}
	}

	/**
	 * Recursive empty dir
	 *
	 * @param string $path    Path.
	 * @param array  $exclude Exclude.
	 *
	 * @return void
	 */
	public static function emptydir( $path, $exclude = array() ) {
		self::rmdir( $path, $exclude, false );
	}

	/**
	 * Check if file is write-able
	 *
	 * @param string $file File.
	 *
	 * @return boolean
	 */
	public static function is_writable( $file ) {
		$exists = file_exists( $file );

		$fp = @fopen( $file, 'a' );

		if ( $fp ) {
			fclose( $fp );

			if ( ! $exists ) {
				@unlink( $file );
			}

			return true;
		}

		return false;
	}

	/**
	 * Cehck if dir is write-able
	 *
	 * @param string $dir Directory.
	 *
	 * @return boolean
	 */
	public static function is_writable_dir( $dir ) {
		$file = $dir . '/' . uniqid( mt_rand() ) . '.tmp';

		return self::is_writable( $file );
	}

	/**
	 * Returns dirname of path
	 *
	 * @param string $path Path.
	 *
	 * @return string
	 */
	public static function dirname( $path ) {
		$dirname = dirname( $path );

		if ( '.' === $dirname || '/' === $dirname || '\\' === $dirname ) {
			$dirname = '';
		}

		return $dirname;
	}

	/**
	 * Make path relative
	 *
	 * @param string $filename File name.
	 * @param string $base_dir Base directory.
	 *
	 * @return string
	 */
	public static function make_relative_path( $filename, $base_dir ) {
		$filename = Util_Environment::realpath( $filename );
		$base_dir = Util_Environment::realpath( $base_dir );

		$filename_parts = explode( '/', trim( $filename, '/' ) );
		$base_dir_parts = explode( '/', trim( $base_dir, '/' ) );

		// count number of equal path parts.
		for ( $equal_number = 0;;$equal_number++ ) {
			if ( $equal_number >= count( $filename_parts ) || $equal_number >= count( $base_dir_parts ) ) {
				break;
			}

			if ( $filename_parts[ $equal_number ] !== $base_dir_parts[ $equal_number ] ) {
				break;
			}
		}

		$relative_dir  = str_repeat( '../', count( $base_dir_parts ) - $equal_number );
		$relative_dir .= implode( '/', array_slice( $filename_parts, $equal_number ) );

		return $relative_dir;
	}

	/**
	 * Returns open basedirs
	 *
	 * @return array
	 */
	public static function get_open_basedirs() {
		$open_basedir_ini = ini_get( 'open_basedir' );
		$open_basedirs    = ( W3TC_WIN ? preg_split( '~[;,]~', $open_basedir_ini ) : explode( ':', $open_basedir_ini ) );
		$result           = array();

		foreach ( $open_basedirs as $open_basedir ) {
			$open_basedir = trim( $open_basedir );
			if ( ! empty( $open_basedir ) && '' !== $open_basedir ) {
				$result[] = Util_Environment::realpath( $open_basedir );
			}
		}

		return $result;
	}

	/**
	 * Checks if path is restricted by open_basedir
	 *
	 * @param string $path Path.
	 *
	 * @return boolean
	 */
	public static function check_open_basedir( $path ) {
		$path          = Util_Environment::realpath( $path );
		$open_basedirs = self::get_open_basedirs();

		if ( ! count( $open_basedirs ) ) {
			return true;
		}

		foreach ( $open_basedirs as $open_basedir ) {
			if ( strstr( $path, $open_basedir ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the octal file permission number of a file or directory.
	 *
	 * @param string $file File path.
	 *
	 * @return int
	 */
	public static function get_file_permissions( $file ) {
		if ( function_exists( 'fileperms' ) && $fileperms = @fileperms( $file ) ) { // phpcs:ignore
			$fileperms = 0777 & $fileperms;
		} else {
			clearstatcache();
			$stat = @stat( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( $stat ) {
				$fileperms = 0777 & $stat['mode'];
			} else {
				$fileperms = 0;
			}
		}
		return intval( decoct( $fileperms ) );
	}

	/**
	 * Get file owner
	 *
	 * @param string $file File path.
	 *
	 * @return string
	 */
	public static function get_file_owner( $file = '' ) {
		$fileowner = 'unknown';
		$filegroup = 'unknown';

		if ( $file ) {
			if ( function_exists( 'fileowner' ) && function_exists( 'fileowner' ) ) {
				$fileowner = @fileowner( $file );
				$filegroup = @filegroup( $file );
				if ( function_exists( 'posix_getpwuid' ) && function_exists( 'posix_getgrgid' ) ) {
					$fileowner = @posix_getpwuid( $fileowner );
					$fileowner = $fileowner['name'];
					$filegroup = @posix_getgrgid( $filegroup );
					$filegroup = $filegroup['name'];
				}
			}
		} elseif ( function_exists( 'getmyuid' ) && function_exists( 'getmygid' ) ) {
			$fileowner = @getmyuid();
			$filegroup = @getmygid();
			if ( function_exists( 'posix_getpwuid' ) && function_exists( 'posix_getgrgid' ) ) {
				$fileowner = @posix_getpwuid( $fileowner );
				$fileowner = $fileowner['name'];
				$filegroup = @posix_getgrgid( $filegroup );
				$filegroup = $filegroup['name'];
			}
		}

		return $fileowner . ':' . $filegroup;
	}

	/**
	 * Creates W3TC_CACHE_TMP_DIR dir if required
	 *
	 * @throws \Exception Exception.
	 *
	 * @return string
	 */
	public static function create_tmp_dir() {
		if ( ! is_dir( W3TC_CACHE_TMP_DIR ) || ! is_writable( W3TC_CACHE_TMP_DIR ) ) {
			self::mkdir_from( W3TC_CACHE_TMP_DIR, W3TC_CACHE_DIR );

			if ( ! is_dir( W3TC_CACHE_TMP_DIR ) || ! is_writable( W3TC_CACHE_TMP_DIR ) ) {
				$e           = error_get_last();
				$description = ( isset( $e['message'] ) ? $e['message'] : '' );

				throw new \Exception(
					\wp_kses_post(
						sprintf(
							// Translators: 1 Cache TMP dir path surround by HTML strong tag, 2 Description.
							\__( 'Can\'t create folder %1$s: %2$s', 'w3-total-cache' ),
							'<strong>' . W3TC_CACHE_TMP_DIR . '</strong>',
							$description
						)
					)
				);
			}
		}

		return W3TC_CACHE_TMP_DIR;
	}

	/**
	 * Atomically writes file inside W3TC_CACHE_DIR dir
	 *
	 * @param unknown $filename Filename.
	 * @param unknown $content  Content.
	 *
	 * @throws \Exception Exception.
	 *
	 * @return void
	 */
	public static function file_put_contents_atomic( $filename, $content ) {
		self::create_tmp_dir();
		$temp = tempnam( W3TC_CACHE_TMP_DIR, 'temp' );

		try {
			$f = @fopen( $temp, 'wb' );
			if ( ! $f ) {
				if ( file_exists( $temp ) ) {
					@unlink( $temp );
				}

				throw new \Exception( 'Can\'t write to temporary file <strong>' . $temp . '</strong>' );
			}

			fwrite( $f, $content );
			fclose( $f );

			if ( ! @rename( $temp, $filename ) ) {
				@unlink( $filename );
				if ( ! @rename( $temp, $filename ) ) {
					self::mkdir_from( dirname( $filename ), W3TC_CACHE_DIR );

					if ( ! @rename( $temp, $filename ) ) {
						throw new \Exception( 'Can\'t write to file <strong>' . $filename . '</strong>' );
					}
				}
			}

			$chmod = 0644;
			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$chmod = FS_CHMOD_FILE;
			}

			@chmod( $filename, $chmod );
		} catch ( \Exception $ex ) {
			if ( file_exists( $temp ) ) {
				@unlink( $temp );
			}

			throw $ex;
		}
	}


	/**
	 * Takes a W3TC settings array and formats it to a PHP String
	 *
	 * @param unknown $data Data.
	 *
	 * @return string
	 */
	public static function format_data_as_settings_file( $data ) {
		$config = "<?php\r\n\r\nreturn array(\r\n";
		foreach ( $data as $key => $value ) {
			$config .= self::format_array_entry_as_settings_file_entry( 1, $key, $value );
		}

		$config .= ');';

		return $config;
	}

	/**
	 * Writes array item to file
	 *
	 * @param int    $tabs  Tabs.
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 *
	 * @return string
	 */
	public static function format_array_entry_as_settings_file_entry( $tabs, $key, $value ) {
		$item = str_repeat( "\t", $tabs );

		if ( is_numeric( $key ) && (string) (int) $key === (string) $key ) {
			$item .= sprintf( '%d => ', $key );
		} else {
			$item .= sprintf( "'%s' => ", addcslashes( $key, "'\\" ) );
		}

		switch ( gettype( $value ) ) {
			case 'object':
			case 'array':
				$item .= "array(\r\n";
				foreach ( (array) $value as $k => $v ) {
					$item .= self::format_array_entry_as_settings_file_entry( $tabs + 1, $k, $v );
				}

				$item .= sprintf( "%s),\r\n", str_repeat( "\t", $tabs ) );

				return $item;

			case 'integer':
				$data = (string) $value;
				break;

			case 'double':
				$data = (string) $value;
				break;

			case 'boolean':
				$data = ( $value ? 'true' : 'false' );
				break;

			case 'NULL':
				$data = 'null';
				break;

			default:
			case 'string':
				$data = "'" . addcslashes( $value, "'\\" ) . "'";
				break;
		}

		$item .= $data . ",\r\n";

		return $item;
	}

	/**
	 * Ensure that ".htaccess" exists in the specified directory.
	 *
	 * If the WP_Filesystem is "direct", then create the file (with mode 0644) if needed.
	 *
	 * @since 2.8.2
	 *
	 * @param  string $dir Directory.
	 * @return bool
	 */
	public static function check_htaccess( string $dir ): bool {
		$filepath = $dir . DIRECTORY_SEPARATOR . '.htaccess';

		if ( ! @file_exists( $filepath ) ) {
			$chmod = 0644;

			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$chmod = FS_CHMOD_FILE;
			}

			$contents = "<IfModule mod_authz_core.c>\n    # Apache 2.4\n    Require all denied\n</IfModule>\n\n<IfModule !mod_authz_core.c>\n    # Apache 2.2\n    Deny from all\n</IfModule>\n";
			return @file_put_contents( $filepath, $contents ) && chmod( $filepath, $chmod );
		}

		return false;
	}
}

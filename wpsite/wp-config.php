<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'test_db' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'UG{DI}6+AFrps|D1c*Q10DNz8okU~]C%f)gFUvHZ{qQVwt 1j?Z-[F)5a~M@K34%' );
define( 'SECURE_AUTH_KEY',  '#FP}qiHD{g!O&rKWvP,S0K%~sC;{T{E26mzccfay(xGR#B@+XU3]bNpGYajqkvab' );
define( 'LOGGED_IN_KEY',    '=!vVQ|+?^8 D;VRF(.8<lWo2%7eor n@>NUWRYWH0*2Id[JL4o_;LDy{>T?Fcs,7' );
define( 'NONCE_KEY',        '6PuD )qe3ID T}c^e4>+)ID&MfW|9oA{Npt|WsFju^G5%1Hf$b%MO*W}8:rjpAIO' );
define( 'AUTH_SALT',        'LOF;AX*!Aam.0;#:)90>hgVeW#7UXM9U9srx{??k_*7HU/:h0848|YmS_dtS>Mc9' );
define( 'SECURE_AUTH_SALT', 'N{Ust/trb;5E6V}S#.#-dSnVO6[@K23`3+y#I=.n(<fI> QALeAB{5`T-+J|/!(;' );
define( 'LOGGED_IN_SALT',   '0joI:6;v9qe{/!4._*s@l9+d<k(EpJeK !]<Nr:rk4T6:=VV!P/28X<}p`!TkC,u' );
define( 'NONCE_SALT',       '^yLm)NEjGmjYq]yJ Q5:E[5?<jNFc,{Tkg2qmNOA50{!pWFB|j;wo;t`kPNn1xkb' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

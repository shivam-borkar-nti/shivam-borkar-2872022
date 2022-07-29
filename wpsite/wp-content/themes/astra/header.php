<?php
/**
 * The header for Astra Theme.
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?><!DOCTYPE html>
<?php astra_html_before(); ?>
<html <?php language_attributes(); ?>>
<head>
<?php astra_head_top(); ?>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="https://gmpg.org/xfn/11">

<?php wp_head(); ?>
<?php astra_head_bottom(); ?>
</head>

<body <?php astra_schema_body(); ?> <?php body_class(); ?>>
<?php astra_body_top(); ?>
<?php wp_body_open(); ?>

<a
	class="skip-link screen-reader-text"
	href="#content"
	role="link"
	title="<?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>">
		<?php echo esc_html( astra_default_strings( 'string-header-skip-link', false ) ); ?>
</a>

<div
<?php
	echo astra_attr(
		'site',
		array(
			'id'    => 'page',
			'class' => 'hfeed site',
		)
	);
	?>
>
	<div id="layout">
<div id="header">
	
   <h1 id="logo"><a href="#!">SimpleEvent</a></h1>
    <span id="slogan">Your slogan goes here</span>
    <hr class="noscreen" />
    <p class="noscreen noprint"> <em>Rychlá navigace: <a href="#!">obsah</a>, <a href="#!">navigace</a>.</em></p>
    <div id="quicknav"> <a href="#!">Home</a> <a href="#!">Contact</a> <a href="#!">Sitemap</a> </div>
    <div id="search">
      <form href="#!" method="post">
        <fieldset>
        <input type="text" id="phrase" name="phrase" value="search phrase" onfocus="if(this.value=='search phrase')this.value=''" />
        <input type="submit" id="submit" value="SEARCH" />
        </fieldset>
      </form>
    </div>
  </div>
  <hr class="noscreen" />
			</div>
	<div class="layout2">
		
  <div id="nav" class="box">
    <ul>
      <li id="active"><a href="#!">Home</a></li>
      <li><a href="#!">Our products</a></li>
      <li><a href="#!">About us</a></li>
      <li><a href="#!">Portfolio</a></li>
      <li><a href="#!" class="nosep">Contacts</a></li>
    </ul>
    <hr class="noscreen" />
  </div>
	</div>

	<?php
	astra_header_before();

	astra_header();

	astra_header_after();

	astra_content_before();
	?>
	<div id="content" class="site-content">
		<div class="ast-container">
		<?php astra_content_top(); ?>

<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Astra
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<?php astra_content_bottom(); ?>
	</div> <!-- ast-container -->
	</div><!-- #content -->
<?php 
	astra_content_after();
		
	astra_footer_before();
		
	astra_footer();
		
	astra_footer_after(); 
?>
	</div><!-- #page -->
<?php 
	astra_body_bottom();    
	wp_footer(); 

?>

<div id="footer">
  <div id="foot">
    <div id="page-bottom"> <a href="#header">Go up</a> </div>
    <p class="f-left">&copy; 2008 - <a href="http://all-free-download.com/free-website-templates/">SimpleEvent</a></p>
    <p class="f-right"><a href="http://www.tvorimestranky.cz" id="webdesign">Webdesign</a>: <a href="http://www.tvorimestranky.cz">TvorimeStranky.cz</a> | Sponsored by: <a href="http://www.topas-tachlovice.cz/topas-tachlovice.aspx">Tachlovice</a></p>
  </div>
</div>
<div align=center style="font-size:12px;">This template  downloaded form <a href='#!'>free website templates</a></div>
	</body>
</html>

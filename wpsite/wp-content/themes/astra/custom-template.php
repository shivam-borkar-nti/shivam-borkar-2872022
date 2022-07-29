<?php /* Template Name: MyCustomTemplate */ ?>
<?php if ( astra_page_layout() == 'left-sidebar' ) : ?>
    <?php get_sidebar(); ?>
<?php endif ?>
    <div id="primary" <?php astra_primary_class(); ?>>
        <?php astra_primary_content_top(); ?>
        <?php astra_content_page_loop(); ?>
        <?php astra_primary_content_bottom(); ?>
    </div><!-- #primary -->
<?php if ( astra_page_layout() == 'right-sidebar' ) : ?>
    <?php get_sidebar(); ?>
<?php endif ?>
<?php get_footer(); ?>
<?php 
global $post;
$session_id = get_post_meta( $post->ID, 'tokbox_session_id', true );
$token = get_post_meta( $post->ID, 'tokbox_token', true );
?>

<?php get_header(); ?>
<div class="column eightcol">
	<?php the_post(); ?>
	<article class="full-post">
		<?php if(has_post_thumbnail() && ThemexCore::getOption('post_image')!='true') { ?>
		<div class="bordered-image post-image">
			<?php the_post_thumbnail('extended', array('class' => 'fullwidth')); ?>
		</div>
		<?php } ?>
		<div class="section-title">
			<h1><?php the_title(); ?></h1>
		</div>
		<?php the_content(); ?>

        <div 
        data-sessionid="<?php echo $session_id; ?>" 
        data-token="<?php echo $token; ?>" 
        class="single-session">
            <button class="vjoinButton" type="submit"><?php _e('Join', 'wp_tokbox'); ?></button>
        </div>

        <!-- Video wrap -->
        <div id="videos">
                <div id="subscriber"></div>
                <div id="publisher"></div>
        </div>
        <!-- End Video Wrap -->
		<footer class="post-footer clearfix">
			<div class="column sixcol">
				<?php if(!ThemexCore::checkOption('post_date')) { ?>
				<span class="icon-calendar post-icon"></span><time class="post-date" datetime="<?php the_time('Y-m-d'); ?>"><?php the_time(get_option('date_format')); ?></time>
				<?php } ?>
				<?php if(!ThemexCore::checkOption('post_author')) { ?>
				<span class="icon-pencil post-icon"></span><span class="post-author"><?php the_author_posts_link(); ?></span>
				<?php } ?>
				<?php if(has_category()) { ?>
				<span class="icon-file-alt post-icon"></span><span class="post-category"><?php the_category(', '); ?></span>
				<?php } ?>
			</div>
			<div class="column sixcol last">
				<div class="tagcloud"><?php the_tags('','',''); ?></div>
			</div>
		</footer>
	</article>
	<?php comments_template(); ?>
</div>
<aside class="sidebar column fourcol last">
<?php get_sidebar(); ?>
</aside>
<?php get_footer(); ?>
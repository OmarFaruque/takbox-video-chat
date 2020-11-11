<?php
/*
Template Name: Profiles
*/


require_once(tokbox_video_chatDIR . 'inc/users.php');



$users=tokboxProfileUsers::getUsers(array(
	'number' => ThemexCore::getOption('user_per_page', 9),
	'offset' => ThemexCore::getOption('user_per_page', 9)*(themex_paged()-1),
));



// foreach($users as $user) {	
// 	echo '<pre>';
// 	print_r($user->data->ID);
// 	echo '</pre>';

// }

$current_user_id = get_current_user_id();
?>
<?php get_header(); ?>
<div class="column eightcol">
	<?php if(!empty($users)) { ?>
 	<div class="profiles-listing clearfix">
		<?php
		$counter=0;
		foreach($users as $user) {

		if( $user->data->ID != $current_user_id ){

		ThemexUser::$data['active_user']=ThemexUser::getUser($user->ID);
		$counter++;
		?>
			<div class="column fourcol <?php if($counter==3) { ?>last<?php } ?>">
			<?php get_template_part('content', 'profile-grid'); ?>
			</div>
			<?php		
			if($counter==3) {
			$counter=0;
			?>
			<div class="clear"></div>
			<?php } ?>
		<?php } } ?>
	</div>
	<!-- /profiles -->
	<?php ThemexInterface::renderPagination(themex_paged(), themex_pages(ThemexUser::getUsers(array('fields' => 'ID')), ThemexCore::getOption('user_per_page', 9))); ?>
	<?php } else { ?>
	<h3><?php _e('No se han encontrado perfiles. &iquest;Probar otra vez?','lovestory'); ?></h3>
	<p><?php _e('Disculpa, no hay perfiles que cumplan tu b&uacute;squeda. Prueba otra vez con otros par&aacute;metros.','lovestory'); ?></p>
	<?php } ?>
</div>
<aside class="sidebar column fourcol last">
<?php get_sidebar(); ?>
</aside>
<?php get_footer(); ?>
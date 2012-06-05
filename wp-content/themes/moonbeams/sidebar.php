<?php if ( is_active_sidebar(1) ) : ?>
	<div id="left">
		<?php if ( !dynamic_sidebar(1) ) : ?>
		<?php endif; ?>
	</div><!--// left -->
<?php endif; ?>

<?php if ( is_active_sidebar(2) ) : ?>
	<div id="right">
		<?php if ( !dynamic_sidebar(2) ) : ?>
		<?php endif; ?>
	</div><!--// right -->
<?php endif; ?>

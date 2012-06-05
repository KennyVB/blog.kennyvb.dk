<?php if ( is_active_sidebar(3) ) : ?>
	<div id="footer_left">
		<?php if ( !dynamic_sidebar(3) ) : ?>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if ( is_active_sidebar(4) ) : ?>
	<div id="footer_right">
		<?php if ( !dynamic_sidebar(4) ) : ?>
		<?php endif; ?>
	</div>
<?php endif; ?>

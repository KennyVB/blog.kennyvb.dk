</div><!--// wrap -->

	<?php wp_nav_menu( array( 'container_class' => 'menu-footer menu-container clearfix', 'theme_location' => 'footer_menu', 'fallback_cb' => ''  ) ); ?>

<div id="footer">
	<div id="copyright">
	<a href="<?php bloginfo('rss2_url'); ?>" class="feed">subscribe to posts</a> or <a href="<?php bloginfo('comments_rss2_url'); ?>" class="feed">subscribe to comments</a><br />
	Powered by WordPress using the <a href="http://www.jusanya.com/moonbeams">Moonbeams Theme</a><br />
	Copyright &copy; <?php echo date('Y'); ?> <a href="<?php echo home_url(); ?>"><?php bloginfo('name'); ?></a>. All Rights Reserved.</div>
</div><!--// footer -->
<?php wp_footer(); ?>
</body>
</html>
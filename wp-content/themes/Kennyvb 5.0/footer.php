</div>
</div>
<div id="footer">
<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the id=main div and all content
 * after.  Calls sidebar-footer.php for bottom widgets.
 *
 * @package WordPress
 * @subpackage Starkers
 * @since Starkers 3.0
 */
?>

<?php
	/* A sidebar in the footer? Yep. You can can customize
	 * your footer with four columns of widgets.
	 */
	get_sidebar( 'footer' );
?>

			<a href="<?php echo home_url( '/' ) ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
			<a href="http://wordpress.org/" title="Semantic Personal Publishing Platform" rel="generator">Proudly powered by WordPress </a>


<div class="admin">
<?php wp_loginout(); ?> &middot; <?php wp_register('', ''); ?>
</div>
<div id="footer-links">
<p>Links</p>
<ul>
<li><a href="http://blog.artofanangel.dk" target="_blank">Blog.ArtOfAnAngel.dk</a></li>
<li><a href="http://www.totallywicked-eliquid.dk/" target="_blank">Totally Wicked</a></li>
<li><a href="http://www.komogvind.dk/" target="_blank">Kom og Vind</a></li>
<li><a href="http://css-tricks.com" target="_blank">Css-tricks.com</a></li>
</ul>
</div>

</div>
<?php
	/* Always have wp_footer() just before the closing </body>
	 * tag of your theme, or you will break many plugins, which
	 * generally use this hook to reference JavaScript files.
	 */

	wp_footer();
?>
</body>
</html>
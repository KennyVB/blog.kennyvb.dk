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

</div>
   </div>
   </div>
   <div id="push">
       </div>
   <div id ="footer">
		<div id="footer-info">

    		<div id="meta">
            <li>
     			<ul>
     			<li><?php wp_register(); ?></li>
     				<li><?php wp_loginout(); ?></li>
     			<li><?php wp_meta(); ?></li>
     			</ul>
     		</li>
     		</div>
     		
    <div id="credits">
     		           	<a href="<?php echo home_url( '/' ) ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
    		<a href="http://wordpress.org/" title="Semantic Personal Publishing Platform" rel="generator">Proudly powered by WordPress </a></div>
    		</div>
<div id="footer_line1"></div>
<div id="footer-links">
<p>Links</p>
<ul class="links_left">
<li><a href="http://www.blog.artofanangel.dk" target="_blank">Blog.ArtOfAnAngel.dk</a></li>
<li><a href="http://www.totallywicked-eliquid.dk/" target="_blank">Totally Wicked</a></li>
<li><a href="http://www.komogvind.dk/" target="_blank">Kom og Vind</a></li>
<li><a href="http://www.css-tricks.com" target="_blank">Css-tricks.com</a></li>
<li><a href="http://www.macbay.dk" target="_blank">MacBay.dk</a></li>
</ul>
<ul class="links_right">
<li><a href="http://www.blog.artofanangel.dk" target="_blank">Blog.ArtOfAnAngel.dk</a></li>
<li><a href="http://www.totallywicked-eliquid.dk/" target="_blank">Totally Wicked</a></li>
<li><a href="http://www.komogvind.dk/" target="_blank">Kom og Vind</a></li>
<li><a href="http://www.css-tricks.com" target="_blank">Css-tricks.com</a></li>
<li><a href="http://www.macbay.dk" target="_blank">MacBay.dk</a></li>
</ul>
</div>



    <div id="footer_line2"></div>
       <div id="twitter">
       <h5>Sidste Tweets</h5>
           <div class="tweet"></div>
           </div>
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
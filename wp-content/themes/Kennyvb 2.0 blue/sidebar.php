<?php
/**
 * The Sidebar containing the primary and secondary widget areas.
 *
 * @package WordPress
 * @subpackage Kennyvb
 * @since Kennyvb 1.0
 */
?>
	<div id="sidebar">
		
		<ul id="main-nav">
		<?php wp_list_pages("title_li=&exclude=4");?>
		</ul>

		<div id="twitter">
		<h4>Twitter</h4>
		<h5>Sidste tweets</h5>
		<div class="tweet"></div> 
		</div>
		<div id="facebook">
		<h4>Facebook</h4>
		<h5>test test</h5>

		</div>
		</div>
	</div>
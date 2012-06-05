=== Random Image Selector ===
Contributors: kdmurray
Version: 1.5.6
Tags: image, images, picture, pictures, random, banner, header, theme plugin
Requires at least: 1.5
Tested up to: 2.9.2
Stable Tag: 1.5.6

== Description ==
This plugin will generate an <IMG> tag for a random image selected from a specified folder.

== Setup Instructions ==
1. Extract randomImage.php into your wp-content/plugins folder (or a subfolder)
2. Activate the plugin in Wordpress
3. On the options screen, select the "Random Image" menu
4. Fill in the values for your physical and http paths
5. Select scaling options (optional)
6. Add some code to whatever page you want to display the image.  This is a great way to
   customize the header image for your wordpress installation.  The plugin will look in
   the folder and randomly select a header image to display.

== Sample Code ==
By placing code like the following in your header, it will create an image tag for you
based on the images in the specified folder.

   &lt;?php
     if (function_exists('generateRandomImgTag'))
     {
         generateRandomImgTag();
     }
   ?&gt;

<!--
The lines above are for the HTML documentation (online)

   <?php
     if (function_exists('generateRandomImgTag'))
     {
         generateRandomImgTag();
     }
   ?>
-->

This code will ensure that the function exists (ie the plugin is active and working) and
call it if it's working correctly.  If the function is missing, or the plugin has been 
removed then the call will be ignored, and no errors will result.

== Feedback ==
kdmurray.at.kdmurray.dot.net

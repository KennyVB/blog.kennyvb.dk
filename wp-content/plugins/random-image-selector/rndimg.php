<?php
  include_once "../../../wp-blog-header.php";
  $img_path = generateRandomImageUrlEnc();
  $im = @imagecreatefromjpeg($img_path);

  if($_GET['captionbar'] == "1") {
    $caption = rawurldecode($img_path);
    $caption = strrchr($caption, "/");
    $caption = substr($caption, 1, strlen($caption) - 4); 
    $imhead = @imagecreatefrompng('img_titlebar.png');
    $textcolor = imagecolorallocate($imhead, 255, 255, 255); 
    $font = imageloadfont('./Tahoma-14.gdf');
    imagestring($imhead, $font, 20, 3, $caption, $textcolor);
    imagecopy($im, $imhead, 8, 1, 0, 0, 960, 22);  
  }

  header('Content-type: image/jpeg');
  imagejpeg($im);
  imagedestroy($im);
?>

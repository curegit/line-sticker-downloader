<?php
// Memory start time
$start_time = microtime(true);
// Get param
$id = (int)filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
// Verify param
if ($id < 1) {
  header('Content-Type: text/plain; charset=UTF-8', true, 400);
  die("ID '$id' is out of range.\n");
}
// Get JSON
$json = @file_get_contents("http://dl.stickershop.line.naver.jp/products/0/0/1/$id/iphone/productInfo.meta");
// Verify ID
if (empty($json)) {
  header('Content-Type: text/plain; charset=UTF-8', true, 400);
  die("ID '$id' does not exist.\n");
}
// Decode JSON
$package_info = json_decode($json, true);
// Construct filename and save destination
$filepath = "./caches/$id.1.linestk.zip";
$filename = basename($filepath);
// Make cache dir
if (!file_exists("./caches")) {
  mkdir("./caches");
}
// Change permission
chmod("./caches", 0777);
// Output and exit if there is the data in cache dir
if (file_exists($filepath) === true) {
  header("Content-Type: application/zip; name=\"$filename\"");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Content-Length: ".filesize($filepath));
  echo file_get_contents($filepath);
  exit;
}
// Set time limit on 5 minutes (No effects in safe mode)
set_time_limit(300);
// Continue even if browser goes back
ignore_user_abort(1);
// Prevent PHP buffering (Helpless to change server settings)
@ini_set("output_buffering", 0);
// Disable compression
@ini_set("zlib.output_compression", 0);
// Tell not to use cache
header('Content-type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("X-Accel-Buffering: no");
// Start flushing
@ob_end_flush();
ob_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Download | Line Sticker Downloader</title>
  </head>
  <body>
    <h1>Download 「<?= h($package_info["title"]["ja"] ?? "日本語名なし") ?> (<?= h($package_info["title"]["en"] ?? "No English name available") ?>)」</h1>
    <p id="console" style="width: 100%; color: #FFF; background-color: #000;">
    </p>
    <p id="download_link"></p>
    <p><a href="./">Back</a></p>
<?php
print_buffer("start...");
print_buffer("Target ID: $id");
ob_flush();
flush();
// Make Zip object
$zip = new ZipArchive();
$result = $zip->open($filepath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
// If it failed to make zip
if ($result !== true) {
  print_buffer("Failed to create zip");
// If it made zip successfully
} else {
  // Check additional contents
  $has_sound = (boolean)($package_info["hasSound"] ?? false) && $package_info["hasSound"] !== "false" || (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "sound") !== false;
  $has_animation = (boolean)($package_info["hasAnimation"] ?? false) && $package_info["hasAnimation"] !== "false";
  $has_popup = (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "popup") !== false;
  // Devices array
  $profiles = array("iPhone", "android", "WindowsPhone", "PC");
  // Each devices
  foreach ($profiles as $profile) {
    // Make dirs for each devices
    $zip->addEmptyDir("$profile");
    // Add JSON
    add_file_to_zip($zip, "$profile/productInfo.meta", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/productInfo.meta");
    // Add tab image
    $tab_images = ["tab_on@2x.png", "tab_off@2x.png", "tab_on.png", "tab_off.png"];
    foreach ($tab_images as $tab_image) {
      add_file_to_zip($zip, "$profile/$tab_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/$tab_image");
    }
    // Add main.png (PC doesn't have main.png)
    $main_images = ["main.png", "main@2x.png"];
    if ($profile !== "PC") {
      foreach ($main_images as $main_image) {
        add_file_to_zip($zip, "$profile/$main_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/$main_image");
      }
    }
    // Add sticker folder
    $zip->addEmptyDir("$profile/stickers");
    // Add sound folder
    if ($has_sound) {
      $zip->addEmptyDir("$profile/sound");
    }
    // Add animation folder
    if ($has_animation) {
      $zip->addEmptyDir("$profile/animation");
    }
    // Add popup folder
    if ($has_popup) {
      $zip->addEmptyDir("$profile/popup");
    }
    // Each stickers
    foreach ($package_info["stickers"] as $sticker) {
      // Add images
      $sticker_id = $sticker["id"];
      $sticker_images = ["{$sticker_id}@2x.png", "{$sticker_id}_key@2x.png", "{$sticker_id}.png", "{$sticker_id}_key.png"];
      foreach ($sticker_images as $sticker_image) {
        add_file_to_zip($zip, "$profile/stickers/$sticker_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/stickers/$sticker_image");
      }
      // Add sound
      if ($has_sound) {
        $sound = "{$sticker_id}.m4a";
        add_file_to_zip($zip, "$profile/sound/$sound", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/sound/$sound");
      }
      // Add animation
      if ($has_animation) {
        $animations = ["{$sticker_id}@2x.png", "{$sticker_id}.png"];
        foreach ($animations as $animation) {
          add_file_to_zip($zip, "$profile/animation/$animation", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/animation/$animation");
        }
      }
      // Add popup
      if ($has_popup) {
        $popups = ["{$sticker_id}@2x.png", "{$sticker_id}.png"];
        foreach ($popups as $popup) {
          add_file_to_zip($zip, "$profile/popup/$popup", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/popup/$popup");
        }
      }
    }
  }
  $zip->close();
  print_buffer("Saved: $filepath");
  $elapsed_time = microtime(true) - $start_time;
  print_buffer("{$elapsed_time} sec");
  print_buffer("Ready to download");
  // Print download link
  echo "    <script>document.getElementById('download_link').innerHTML = '<a href=\"{$filepath}\">Download</a>';</script>\n";
  ob_flush();
  flush();
}
// Delete outdated caches
$caches = glob("./caches/*.zip");
foreach($caches as $cache){
  if(is_file($cache)) {
    if (time() - filemtime($cache) > 60 * 60 * 24 * 30) {
      if (@unlink($cache)) {
        print_buffer("Server cache cleaned: $cache");
      }
    }
  }
}
?>
  </body>
</html>
<?php
ob_flush();
flush();
// Sanitize HTML
function h($html) {
  return htmlspecialchars($html, ENT_QUOTES, "UTF-8");
}
// Console-like print
function print_buffer($str) {
  echo "    <!-- dammy data: ".str_pad("", 77760, "アイ！カツ！")." -->\n"; // Send dummy to force browser to render
  echo "    <script>document.getElementById('console').insertAdjacentHTML('beforeEnd', '$str<br>');</script>\n";
  ob_flush();
  flush();
}
// Add downloaded content to zip and print progress
function add_file_to_zip($zip, $filename, $url) {
  static $file_count = 0;
  $file_count++;
  $content = @file_get_contents($url);
  if ($content === false) {
    print_buffer("None: $filename ($file_count)");
  } else {
    $result = $zip->addFromString($filename, $content);
    if ($result === true) {
      print_buffer("Done: $filename ($file_count)");
    } else {
      print_buffer("Failed: $filename ($file_count)");
    }
  }
}

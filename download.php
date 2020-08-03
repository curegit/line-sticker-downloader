<?php
// Include settings
require_once __DIR__."/settings.php";
// Memory start time
$start_time = microtime(true);
// Get param (CGI)
$id = (int)filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$cli = false; // Global
// CLI args
if ($id < 1) {
  $id = (int)filter_var($argv[1] ?? 0, FILTER_VALIDATE_INT);
  $cli = true; // Global
}
$savepath = (string)filter_var($argv[2] ?? "");
// Verify param
if ($id < 1) {
  header("Content-Type: text/plain; charset=UTF-8", true, 400);
  echo "ID '$id' is out of range.".PHP_EOL;
  die(1);
}
if ($savepath !== "") {
  if (!file_exists(dirname($savepath))) {
    echo "No such directory\n";
    die(1);
  }
  if (file_exists($savepath) && is_dir($savepath)) {
    echo "Destination filepath is a directory".PHP_EOL;
    die(1);
  }
}
// Get JSON
$json = @file_get_contents("http://dl.stickershop.line.naver.jp/products/0/0/1/$id/iphone/productInfo.meta");
// Verify ID
if (empty($json)) {
  header("Content-Type: text/plain; charset=UTF-8", true, 400);
  echo "ID '$id' does not exist.".PHP_EOL;
  die(1);
}
// Decode JSON
$package_info = json_decode($json, true);
// Construct filename and save destination
$cachedir = __DIR__."/caches";
$clipath = $savepath === "" ? "$id.1.linestk.zip" : $savepath;
$filepath = Cache !== 0 || !$cli ? "$cachedir/$id.1.linestk.zip" : $clipath;
$filename = basename($filepath);
$webpath = "caches/$filename";
$cachepath = "$cachedir/$filename";
// Output and exit if there is the data in cache dir
if (Cache !== 0) {
  if (file_exists($cachepath) === true) {
    // CLI
    if ($cli) {
      print_line("Cache exists");
      if (@copy($cachepath, $clipath) === false) {
        print_line("Failed to save zip");
        die(1);
      }
      print_line("Saved: $clipath");
    // CGI
    } else {
      header("Content-Type: application/zip; name=\"$filename\"");
      header("Content-Disposition: attachment; filename=\"$filename\"");
      header("Content-Length: ".filesize($cachepath));
      echo file_get_contents($cachepath);
    }
    exit(0);
  }
}
// Tricks for CGI
if (!$cli) {
  // Set time limit on 5 minutes (No effects in safe mode)
  set_time_limit(300);
  // Continue even if browser goes back
  ignore_user_abort(1);
  // Prevent PHP buffering (Helpless to change server settings)
  @ini_set("output_buffering", 0);
  // Disable compression
  @ini_set("zlib.output_compression", 0);
  // Tell not to use cache
  header("Content-type: text/html; charset=utf-8");
  header("Cache-Control: no-cache, must-revalidate");
  header("X-Accel-Buffering: no");
}
// CLI mode
if ($cli) {
  print_line(($package_info["title"]["ja"] ?? "日本語名なし")." (".($package_info["title"]["en"] ?? "No English name available").")");
  print_line("Start...");
  print_line("Target ID: $id");
// CGI mode
} else {
  // Start flushing
  @ob_end_flush();
  ob_start();
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Download | Line Sticker Downloader</title>
    <link href="console.css" rel="stylesheet">
  </head>
  <body>
    <h1>Downloading「<?= h($package_info["title"]["ja"] ?? "日本語名なし") ?> (<?= h($package_info["title"]["en"] ?? "No English name available") ?>)」</h1>
    <p><a href="./">Back</a></p>
    <p class="download_link"></p>
    <p id="console"></p>
    <p class="download_link"></p>
    <p><a href="./">Back</a></p>
<?php
  print_line("Start...");
  print_line("Target ID: $id");
  ob_flush();
  flush();
}
// Make Zip object
$zip = new ZipArchive();
$result = @$zip->open($filepath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
// If it failed to make zip
if ($result !== true) {
  $e = 1;
  print_line("Failed to create zip");
// If it made zip successfully
} else {
  // Check additional contents
  $has_sound = (boolean)($package_info["hasSound"] ?? false) && $package_info["hasSound"] !== "false" || (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "sound") !== false;
  $has_animation = (boolean)($package_info["hasAnimation"] ?? false) && $package_info["hasAnimation"] !== "false";
  $has_popup = (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "popup") !== false;
  $is_custom_text = (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "NAME_TEXT") !== false;
  $is_free_text = (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "PER_STICKER_TEXT") !== false;
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
    // Add popup (animated background) folder
    if ($has_popup) {
      $zip->addEmptyDir("$profile/popup");
    }
    // Add base folder
    if ($is_custom_text) {
      $zip->addEmptyDir("$profile/base");
    }
    // Add plus folders
    if ($is_free_text) {
      $zip->addEmptyDir("$profile/base/plus");
      $zip->addEmptyDir("$profile/overlay/plus/default");
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
      // Add popup (animated background)
      if ($has_popup) {
        $popups = ["{$sticker_id}@2x.png", "{$sticker_id}.png"];
        foreach ($popups as $popup) {
          add_file_to_zip($zip, "$profile/popup/$popup", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/popup/$popup");
        }
      }
      // Add custom text base
      if ($is_custom_text) {
        $bases = ["@2x.png", ".png"];
        foreach ($bases as $base) {
          add_file_to_zip($zip, "$profile/base/{$sticker_id}{$base}", "https://stickershop.line-scdn.net/stickershop/v1/sticker/$sticker_id/$profile/base/sticker{$base}");
        }
      }
      // Add free text base and overlay
      if ($is_free_text) {
        $types = ["@2x.png", ".png"];
        foreach ($types as $type) {
          add_file_to_zip($zip, "$profile/base/plus/{$sticker_id}{$type}", "https://stickershop.line-scdn.net/stickershop/v1/sticker/$sticker_id/$profile/base/plus/sticker{$type}");
          add_file_to_zip($zip, "$profile/overlay/plus/default/{$sticker_id}{$type}", "https://stickershop.line-scdn.net/stickershop/v1/product/{$id}/sticker/$sticker_id/$profile/overlay/plus/default/sticker{$type}");
        }
      }
    }
  }
  // Saving
  if (@$zip->close() === false) {
    $e = 1;
    print_line("Failed to save zip");
  } else {
    if (Cache !== 0) {
      print_line("Cache saved: $filepath");
    }
    $elapsed_time = microtime(true) - $start_time;
    print_line("{$elapsed_time} sec");
    if (!$cli) {
      print_line("Ready to download");
    }
    // Copy to target dir (CLI)
    if ($cli) {
      if (Cache === 0) {
        print_line("Saved: $clipath");
      } else {
        if (@copy($filepath, $clipath) === false) {
          $e = 1;
          print_line("Failed to save zip");
        } else {
          print_line("Saved: $clipath");
        }
      }
    // Print download link (CGI)
    } else {
      echo "    <script>var es = document.getElementsByClassName('download_link'); for(var i = 0; i < es.length; i++) { es[i].innerHTML = '<a href=\"{$webpath}\" download>Download</a>'; }</script>".PHP_EOL;
      ob_flush();
      flush();
    }
  }
}
// Delete outdated caches
$caches = glob("$cachedir/*.zip");
foreach($caches as $cache) {
  if(is_file($cache)) {
    if (time() - filemtime($cache) > (Cache <= 0 ? 60 * Tmp : 60 * 60 * 24 * Cache)) {
      if (@unlink($cache)) {
        print_line("Server cache cleaned: $cache");
      }
    }
  }
}
// End of document
if (!$cli) {
?>
  </body>
</html>
<?php
  ob_flush();
  flush();
}
return ($e ?? 0);
// Sanitize HTML
function h($html) {
  return htmlspecialchars($html, ENT_QUOTES, "UTF-8");
}
// Console-like print
function print_line($str) {
  global $cli;
  if ($cli) {
    echo "$str".PHP_EOL;
  } else {
    echo "    <!-- dummy data: ".str_pad("", 3600, "アイ！カツ！")." -->".PHP_EOL; // Send dummy to force browser to render
    echo "    <script>document.getElementById('console').insertAdjacentHTML('beforeEnd', '".h($str)."<br>');</script>".PHP_EOL;
    ob_flush();
    flush();
  }
}
// Add downloaded content to zip and print progress
function add_file_to_zip($zip, $filename, $url) {
  static $file_count = 0;
  $file_count++;
  $content = @file_get_contents($url);
  if ($content === false) {
    print_line("None: $filename ($file_count)");
  } else {
    $result = $zip->addFromString($filename, $content);
    if ($result === true) {
      print_line("Done: $filename ($file_count)");
    } else {
      print_line("Failed: $filename ($file_count)");
    }
  }
}

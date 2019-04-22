<?php
// 処理時間を計り始める
$start_time = microtime(true);
// パラメータを受け取る
$id = (int)filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
// IDを検証する
if ($id < 1) {
  header('Content-Type: text/plain; charset=UTF-8', true, 400);
  die("ID '$id' is out of range.\n");
}
// IDでJSONを取得
$json = @file_get_contents("http://dl.stickershop.line.naver.jp/products/0/0/1/$id/iphone/productInfo.meta");
// IDの存在を確認
if (empty($json)) {
  header('Content-Type: text/plain; charset=UTF-8', true, 400);
  die("ID '$id' does not exist.\n");
}
// JSONをデコードして情報を連想配列に格納
$package_info = json_decode($json, true);
// ファイル名と保存パスを構成
$filepath = "./caches/$id.1.linestk.zip";
$filename = basename($filepath);
// 一時ファイルを保存するディレクトリをつくる
if (!file_exists("./caches")) {
  mkdir("./caches");
}
// ディレクトリの書き込み権限を変更
chmod("./caches", 0777);
// キャッシュに残っていればそれをダウンロードさせて終了
if (file_exists($filepath) === true) {
  header("Content-Type: application/zip; name=\"$filename\"");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Content-Length: ".filesize($filepath));
  echo file_get_contents($filepath);
  exit;
}
// 処理制限時間を5分にする（PHPのセーフモードが有効だと機能しない）
set_time_limit(300);
// ブラウザが切断しても処理を続ける
ignore_user_abort(1);
// PHPのバッファリングを無効（サーバプログラムでかかっている場合とかは意味ない）
@ini_set("output_buffering", 0);
// 圧縮を無効化
@ini_set("zlib.output_compression", 0);
// キャッシュ無効のヘッダを出力
header('Content-type: text/html; charset=utf-8');
header("Cache-Control: no-cache, must-revalidate");
header("X-Accel-Buffering: no");
// バッファの小出しを始める
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
// Zipオブジェクトを用意する
$zip = new ZipArchive();
$result = $zip->open($filepath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
// Zipの作成に失敗したとき
if ($result !== true) {
  print_buffer("Failed to create zip");
// Zipの作成に成功したとき
} else {
  // 追加リソースの有無を調べる
  $has_sound = (boolean)($package_info["hasSound"] ?? false) && $package_info["hasSound"] !== "false" || (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "sound") !== false;
  $has_animation = (boolean)($package_info["hasAnimation"] ?? false) && $package_info["hasAnimation"] !== "false";
  $has_popup = (boolean)($package_info["stickerResourceType"] ?? false) && stristr($package_info["stickerResourceType"], "popup") !== false;
  // プロファイルの種類
  $profiles = array("iPhone", "android", "WindowsPhone", "PC");
  // プロファイル別のフォルダ
  foreach ($profiles as $profile) {
    // プロファイル別のフォルダ
    $zip->addEmptyDir("$profile");
    // パッケージ情報ファイル（JSON）を追加
    add_file_to_zip($zip, "$profile/productInfo.meta", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/productInfo.meta");
    // タブ画像を追加
    $tab_images = ["tab_on@2x.png", "tab_off@2x.png", "tab_on.png", "tab_off.png"];
    foreach ($tab_images as $tab_image) {
      add_file_to_zip($zip, "$profile/$tab_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/$tab_image");
    }
    // main.pngを追加（PC版にはmain.pngが存在しない）
    $main_images = ["main.png", "main@2x.png"];
    if ($profile !== "PC") {
      foreach ($main_images as $main_image) {
        add_file_to_zip($zip, "$profile/$main_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/$main_image");
      }
    }
    // スタンプフォルダ追加
    $zip->addEmptyDir("$profile/stickers");
    // 音ありなら音フォルダ追加
    if ($has_sound) {
      $zip->addEmptyDir("$profile/sound");
    }
    // アニメーションありならアニメーションフォルダ追加
    if ($has_animation) {
      $zip->addEmptyDir("$profile/animation");
    }
    // ポップアップありならフォルダ追加
    if ($has_popup) {
      $zip->addEmptyDir("$profile/popup");
    }
    // スタンプファイルを追加する
    foreach ($package_info["stickers"] as $sticker) {
      // 画像を追加
      $sticker_id = $sticker["id"];
      $sticker_images = ["{$sticker_id}@2x.png", "{$sticker_id}_key@2x.png", "{$sticker_id}.png", "{$sticker_id}_key.png"];
      foreach ($sticker_images as $sticker_image) {
        add_file_to_zip($zip, "$profile/stickers/$sticker_image", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/stickers/$sticker_image");
      }
      // 音ありなら追加
      if ($has_sound) {
        $sound = "{$sticker_id}.m4a";
        add_file_to_zip($zip, "$profile/sound/$sound", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/sound/$sound");
      }
      // アニメーションありなら追加
      if ($has_animation) {
        $animations = ["{$sticker_id}@2x.png", "{$sticker_id}.png"];
        foreach ($animations as $animation) {
          add_file_to_zip($zip, "$profile/animation/$animation", "http://dl.stickershop.line.naver.jp/products/0/0/1/$id/$profile/animation/$animation");
        }
      }
      // ポップアップありなら追加
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
  // ダウンロードリンクを出力
  echo "    <script>document.getElementById('download_link').innerHTML = '<a href=\"{$filepath}\">Download</a>';</script>\n";
  ob_flush();
  flush();
}
// 古いキャッシュを削除
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
// HTMLエスケープ関数
function h($html) {
  return htmlspecialchars($html, ENT_QUOTES, "UTF-8");
}
// 進捗コンソールに行を追加するスクリプトを吐くバッファを出力する関数
function print_buffer($str) {
  echo "    <!-- dammy data: ".str_pad("", 77760, "アイ！カツ！")." -->\n"; // ダミーデータを送りつけてサーバー、ブラウザに表示を促す
  echo "    <script>document.getElementById('console').insertAdjacentHTML('beforeEnd', '$str<br>');</script>\n";
  ob_flush();
  flush();
}
// ZipにGETしてきたファイルを追加して処理結果を出力する関数
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

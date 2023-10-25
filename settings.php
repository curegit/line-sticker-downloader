<?php
// Days to keep caches (set 0 to disable caching)
const Cache = 0;
// Minutes to keep CGI temporary files if caching is disabled
const Tmp = 120;

// When this file accessed directly
if (!debug_backtrace()) {
  http_response_code(403);
  header("Content-type: text/plain; charset=utf-8");
}

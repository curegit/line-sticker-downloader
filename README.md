# Line Sticker Downloader

Simple tool for downloading sticker images and assets in a zip from LINE Store

It works in both browser-based and command-line environments.

## Features

- Compatible with sound and animation stickers
- Can be used in Mod-PHP, CGI (browser), and CLI (terminal) environments

## Requirements

- PHP >= 7.0
- PHP Zip extension

### Debian example (CLI usage only)

`sudo apt install php-cli php-zip`

## Installation

### CGI and CLI

1. Clone the repository, or download zip and extract it
2. Deploy the entire directory to a web server (personal private server recommended)
3. Give PHP permission to write in `caches` directory (0777 is fine)

### CLI only

1. Clone the repository, or download zip and extract it

## Usage

### CGI

1. Visit `index.html`
2. Enter the sticker ID
3. Click the download button
4. Wait for a minute
5. Click the download link

### CLI

- To download stickers with ID 1234, run:

  `php download.php 1234`

- To download stickers with ID 1111111 and save them as `one.zip` in the working directory, run:

  `php download.php 1111111 one.zip`

- To download stickers with ID 5678 and save them as `abc.zip` in an existing `foo` directory, run:

  `php download.php 5678 foo/abc.zip`

Existing files will be overwritten.

## Tips

### How to find IDs

See the URL of sticker item pages.

![Store](images/store_screen.png)

### IDs are consecutive

- Official stickers are numbered starting from 1
- Creator's stickers are numbered starting from 1000000

### Download multiple sticker packages

You can use the CLI with a loop command to download multiple sticker packages.

- Bash

```bash
for ((i=1000; i<=1050; i++)); do php download.php $i; done
```

- PowerShell

```ps1
for ($i = 1000; $i -lt 1050; $i++) { php download.php $i }
```

#### Multiprocessing downloading

You can use xargs to download multiple sticker packages in parallel.

```sh
seq 1000 2000 | xargs -L 1 -P 8 php download.php
```

If you are using PowerShell 7.0 or newer, `ForEach-Object -Parallel` is a suitable alternative.

```ps1
2000..3000 | ForEach-Object -ThrottleLimit 8 -Parallel { php download.php $_ }
```

## Notes

- The size of a download page (CGI) may be slightly large because the PHP program outputs a lot of dummy data so that a server sends document data continuously and a browser refreshes the screen.
- The sticker resources are located in public web directories, so anyone can access them easily and legally (for private use only).
- A few packages contain broken PNGs in iPhone stickers, missing important metadata. These stickers can't open with most applications, but there is a solution. Open them with macOS's Preview application and export as new images. The exported images may be valid forms.

## Related

[Line Theme Downloader](https://github.com/curegit/line-theme-downloader)

## License

[WTFPL](LICENSE)

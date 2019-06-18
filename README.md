# Line Sticker Downloader

Download sticker images and assets in zip from LINE Store

## Features

- Compatible sound and animation stickers
- Able to run from both CGI (browser) and CLI (terminal)

## Requirements

- PHP 7.0+
- PHP ZipArchive

#### Debian example (CLI usage only)

`sudo apt install php-cli php-zip`

## Installation

### CGI and CLI

1. Clone the repository, or download zip and extract it
2. Deploy the entire directory to a web server (personal server recommended)
3. Give PHP permission to write in `caches` directory (0777 is common)

### CLI only

1. Clone the repository, or download zip and extract it

## Usage

### CGI

1. Visit `index.html`
2. Fill ID
3. Click a download button
4. Wait
5. Click a download link

### CLI

- Download ID 1234 stickers

  `php download.php 1234`
  
- Download ID 1111111 stickers as `one.zip` in working directory

  `php download.php 1111111 one.zip`

- Download ID 5678 stickers as `abc.zip` in `foo` directory

  `php download.php 5678 foo/abc.zip`

## Tips

### How to find IDs

See the url of a sticker item page.

![Store](store_screen.png)

### IDs are consecutive

- Official stickers are from 1
- Creator's stickers are from 1000000

### Download multiple sticker packages

Use CLI with loop command.

- Bash

```bash
for ((i=1000; i<=1050; i++)); do php download.php $i; done
```

- Powershell

```ps
for ($i = 1000; $i -lt 1050; $i++) {
  php download.php $i
}
```

#### Multiprocessing downloading

xargs may help.

```sh
seq 1000 2000 | xargs -L 1 -P 8 php download.php
```

## Notes

- The size of a download page would be large because the PHP program outputs a lot of dummy data so that a server sends document data continuously, and a browser refresh the screen.
- Sticker resources are located in public web directories, so anyone can get them easily and legally (private use only).
- A few packages contain broken PNGs in iPhone stickers, missing important metadata. These stickers can't open with most applications, but there is a solution. Open them with OS X's application (Preview etc.) and export as new images. Exported images may be valid form.

## License

[WTFPL](LICENSE)

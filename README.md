# Line Sticker Downloader

Download sticker images and assets in a zip from LINE Store

## Features

The download script is able to run from both CGI (browser) and CLI (terminal).

## Requirements

- PHP 7
- PHP ZipArchive

#### Debian example (CLI usage only)

`sudo apt install php-cli php-zip`

## Installation

### CGI and CLI

1. Download zip
2. Extract it to any web directory (personal server recommended)
3. Give PHP's write permission to `caches` directory (0777 is common)

### CLI

1. Download zip
2. Extract it to anywhere

## Usage

### CGI

1. Go to index.php
2. Fill ID
3. Click "Download" button
4. Wait
5. Click "Download" link

### CLI

- Download id 1234 stickers

  `php download.php 1234`
  
- Download id 1111111 stickers as `one.zip` in working directory

  `php download.php 1111111 one.zip`

- Download id 5678 stickers as `abc.zip` in `foo` directory

  `php download.php 5678 foo/abc.zip`

## Tips

### How to find IDs

See url of the store page.

### IDs are consecutive

- Official stickers are from 1
- Creator's stickers are from 1000000

### Download multiple sticker packages

Use CLI with loop command.

`for ((i=1000; i<=1050; i++)); do php download.php $i; done`

## Note

- The document size of download page would be large because the PHP program outputs a lot of dummy data so that a server sends document data continuously and a browser refresh the screen.
- Sticker resources are located in public web directories, so anyone can get them easily and legally (private use only).

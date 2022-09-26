# NFT Manager

This package helps the management of a NFT collection.
Given that you already generated a (generative) NFT collection with images and metadata, you can use this tool to perform additional operations, such as **obfuscation** (for partial revealing), **update metadata and traits**, and others.

## Installation

The preferred method of installation is via Composer. Run the following command to install the package and add it as a requirement to your project's composer.json:

`composer require inmarelibero/nft-manager`

## Operations

In a single PHP script you can **define one or more steps** that will be **processed sequentially** and independently.

For example, you can:
1. **obfuscate metadata** leaving the first 100 NFTs in clear
1. then **update the "name"** metadata with the format `My super NFT #{id}`
1. and finally **format the metadata** to have a standard format

You can choose to apply an Operation only to a subset of NFTs, by using the `from_id`. 

Available operations are:

### 1) Update Metadata
Useful to update the metadata of every NFT, including:
- update `attributes`
- remove `attributes`
- remove `metadata` by key
- update `url` of the "image" metadata
- update `external_url`
- update `name`

### 2) Format Metadata
Run a standard formatter that:
- order metadata by importante
- order attributes alphabetically

### 3) Obfuscation
Lets you replace the `attributes` with a placeholder (eg. "?") to obfuscate traits.

Useful when doing partial revealings, because you can specify a starting ID to obfuscate from.

#### 4) Renumbering
Used when:
- your NFTs start from 0 and you want to renumber them starting from 1
- there are holes in your collection

#### 5) Shuffle
Shuffle the entire collection randomly.

## Usage

- start from an empty folder, eg. `mkdir my_project`
- enter the folder: `cd my_project`
- install this package: `composer require inmarelibero/nft-manager`
- create folder `my_project/input` with the following subfolders:
    - `input/`
        - `images/`     <- put all the NFT images here
        - `metadata/`   <- put all the NFT metadata here
- create a php file eg. `/run.php` (see section <a name="Run script">Run script</a> for the content)
- run script: `php run.php`
- in the `/output` folder you will find the resulting collection

### Run script

You must create one PHP script, defining the list of Operations that will be applied sequentially to your input NFT collection.

```
<?php

require __DIR__ . '/vendor/autoload.php';

use \Inmarelibero\NFTManager\Operation\OperationFormatMetadata
use \Inmarelibero\NFTManager\Operation\OperationObfuscation
use \Inmarelibero\NFTManager\Operation\OperationRenumbering
use \Inmarelibero\NFTManager\Operation\OperationShuffle
use \Inmarelibero\NFTManager\Operation\OperationUpdateMetadata

// the ID of the last minted NFT (can be null)
$lastMintedId = null;

$nftHandler = new \Inmarelibero\NFTManager\NFTManager();

/*
 * renumber IDs
 */
$nftHandler->run(OperationRenumbering::class, [
    // the collection will start from NFT #0
    'start_from_id' => 0,
    
    // the "name" of the NFT #0 will be "https://examples.com/metadata/0.png"
    'update_metadata_image' => 'https://examples.com/metadata/{id}.png',
    
    // the "name" of the NFT #0 will be "Wow NFT #0"
    'update_metadata_name' => 'Wow NFT #{id}',
]);

/*
 * obfuscate
 */
$nftHandler->run(OperationObfuscation::class, [
    // if $lastMintedId is 10, then NFT from #0 to #9 will be left in clear, while NFT from #10 and following will be obfuscated
    'from_id' => $lastMintedId !== null ? $lastMintedId + 1 : null,
    
    // the absolute path of the image to use as placeholder, for obfuscated NFTs
    'placeholder_image_absolute_path' => getcwd() ."/assets/placeholder.png",
    
    // all obfuscated NFTs will have this string as value in every trait
    'placeholder_value' => '?',
]);

/*
 * update metadata
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    'from_id' => $lastMintedId !== null ? $lastMintedId + 1 : null,
    
    // add attribute "Legendary": "?"
    'update_attributes' => [
        'Legendary' => '?',
    ],
    
    // remove the following attributes
    'remove_attributes' => [
        'Hat Accessories',
        'Glasses',
        'Mouth',
        'Necklace',
        'Piercings',
        'Earrings',
    ],
]);

/*
 * update metadata: add trait "Genesis Collection
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    // add this trait to all NFTs
    'update_attributes' => [
        'Genesis Collection' => 'Genesis Collection',
    ],
]);

/*
 * update metadata: remove "dna"
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    // remove, if present, the "dna" metadata key and value
    'remove_values' => [
        'dna',
    ],
]);

/*
 * update metadata: update base URI
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    // all "image" metadata will be updated
    'update_base_uri' => 'https://example.com/metadata/',
]);

/*
 * update metadata: update "name"
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    'update_name' => 'CryptoNFT #{id}',
]);

/*
 * update metadata: update "external_url"
 */
$nftHandler->run(OperationUpdateMetadata::class, [
    'update_external_url' => 'https://foocollection.com',
]);

/*
 * Shuffle the entire collection and update "image" and "name" metadata
 */
$nftHandler->run(OperationShuffle::class, [
    'update_metadata_image' => 'https://foocollection.com/{id}.png',
    'update_metadata_name' => 'Foo NFT #{id}',
]);

/*
 * Format metadata applying a standard format
 */
$nftHandler->run(OperationFormatMetadata::class);
```

## Documentation

Please see `doc/` folder for documentation and examples.

## Copyright and License

The inmarelibero/nft-manager library is copyright © Emanuele Gaspari Castelletti and licensed for use under the MIT License (MIT). Please see LICENSE for more information.
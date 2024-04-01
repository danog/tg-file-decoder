# tg-file-decoder

![build](https://github.com/danog/tg-file-decoder/workflows/build/badge.svg)

Decode [Telegram bot API file IDs](https://core.telegram.org).

## Install

```bash
composer require danog/tg-file-decoder
```

On 32-bit systems, [phpseclib](https://github.com/phpseclib/phpseclib) is also required.

## Examples:

### Decoding bot API file IDs

```php
use danog\Decoder\FileId;
use danog\Decoder\UniqueFileId;

$fileId = FileId::fromBotAPI('CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ');

$version = $fileId->getVersion(); // bot API file ID version, usually 4
$subVersion = $fileId->getSubVersion(); // bot API file ID subversion, equivalent to a specific tdlib version

$dcId = $fileId->getDcId(); // On which datacenter is this file stored

$type = $fileId->getType(); // File type
$typeName = $fileId->getTypeName(); // File type (as string)

$id = $fileId->getId();
$accessHash = $fileId->getAccessHash();

$fileReference = $fileId->getFileReference(); // File reference, https://core.telegram.org/api/file_reference
$url = $fileId->getUrl(); // URL, file IDs with encoded webLocation

// You can also use hasFileReference and hasUrl
$fileIdReencoded = $fileId->getBotAPI(); // CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ
$fileIdReencoded = (string) $fileId;     // CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ

// For photos, thumbnails if ($fileId->getType() <= PHOTO)
$volumeId = $fileId->getVolumeID();
$localId = $fileId->getLocalID();

// if $fileId->hasPhotoSizeSource()
$photoSizeSource = $fileId->getPhotoSizeSource(); // PhotoSizeSource object
$photoSizeSource->getDialogId();
$photoSizeSource->getStickerSetId();

// And more, depending on photosize source
```

The bot API subversion, present since file IDs v4, is equivalent to the [version of tdlib](https://github.com/tdlib/td/blob/master/td/telegram/Version.h#L13) used server-side in the bot API.

For file types, see [file types](#bot-api-file-id-types).
For photosize source, see [here](#photosize-source).

### Decoding bot API unique file IDs

```php
$uniqueFileId = UniqueFileId::fromUniqueBotAPI('AgADrQEAArE4rFE');

$type = $fileId->getType(); // Unique file type
$typeName = $fileId->getTypeName(); // Unique file type (as string)

$id = $uniqueFileId->getId();
$accessHash = $uniqueFileId->getAccessHash();
$url = $uniqueFileId->getUrl(); // URL, for unique file IDs with encoded webLocation
// You can also use hasUrl

// For photos
$volumeId = $uniqueFileId->getVolumeID();
$localId = $uniqueFileId->getLocalID();
```

For unique file types, see [unique types](#bot-api-unique-file-id-types).

### Extracting unique file IDs from full file IDs

```php
$full = 'CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ';
$unique = 'AgADrQEAArE4rFE';

$fileId = FileId::fromBotAPI($full);
$uniqueFileId = UniqueFileId::fromUniqueBotAPI($unique);
$uniqueFileIdExtracted1 = UniqueFileId::fromBotAPI($full);

$uniqueFileIdExtracted2 = $fileId->getUniqueBotAPI();

var_dump(((string) $uniqueFileId) === ((string) $uniqueFileIdExtracted1)); // true, always AgADrQEAArE4rFE!
var_dump(((string) $uniqueFileId) === ((string) $uniqueFileIdExtracted2)); // true, always AgADrQEAArE4rFE!
```

### Photosize source

```php
$fileId = FileId::fromString('CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ');

$photoSizeSource = $fileId->getPhotoSizeSource(); // PhotoSizeSource object

$sourceType = $photoSizeSource->getType();

// If $sourceType === PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL|PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL or 
// If $photoSizeSource instanceof PhotoSizeSourceDialogPhoto
$dialogId = $photoSizeSource->getDialogId();
$dialogId = $photoSizeSource->getStickerSetId();
```

The `PhotoSizeSource` abstract base class indicates the source of a specific photosize from a chat photo, photo, stickerset thumbnail, file thumbnail.
Each photosize type (`getType`) is mapped to a specific subclass of the `PhotoSizeSource` abstract class, returned when calling getPhotoSizeSource.
The photosize type is one of:

* `const PHOTOSIZE_SOURCE_LEGACY = 0` - Legacy, used for file IDs with the deprecated `secret` field, returns [PhotoSizeSourceLegacy](#photosizesourcelegacy) class.
* `const PHOTOSIZE_SOURCE_THUMBNAIL = 1` - Used for document and photo thumbnail, returns [PhotoSizeSourceThumbnail](#photosizesourcethumbnail) class.
* `const PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL = 2` - Used for dialog photos, returns [PhotoSizeSourceDialogPhoto](#photosizesourcedialogphoto) class.
* `const PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG = 3` - Used for dialog photos, returns [PhotoSizeSourceDialogPhoto](#photosizesourcedialogphoto) class.
* `const PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL = 4` - Used for document and photo thumbnails, returns [PhotoSizeSourceThumbnail](#photosizesourcethumbnail) class.

### Building custom file IDs

```php
$fileId = new FileId;

$fileId->setType(STICKER);
$fileId->setId($id);
$fileId->setAccessHash($customHash);

// You get it...

$encoded = (string) $fileId; // CAACAgQAAxkDAAJEsl44nl3yxPZ8biI8uhaA7rbQceOSAAKtAQACsTisUXvMEbVnTuQkGAQ, or something
```

All classes, from [FileId](#fileid), to [UniqueFileID](#uniquefileid), to [PhotoSizeSource](PhotoSizeSourceDialogPhoto) can be built using `set` methods for each and every field.

### Bot API file ID types

The file type is a numeric constant indicating the type of file, (the constant is always in the `danog\Decoder` namespace).
The file type name is a string version of the file type, typically the one used in bot API file objects.  

The `TYPES` array contains a `file type` => `file type name` map.
The `TYPES_IDS` array contains a `file type name` => `file type` map.

`const CONSTANTNAME = value` - Description (`type name`)

* `const THUMBNAIL = 0` - Thumbnail (`thumbnail`)
* `const PROFILE_PHOTO = 1` - Profile photo; used for users, supergroups and channels, chat photos are normal PHOTOs (`profile_photo`)
* `const PHOTO = 2` - Photo (`photo`)
* `const VOICE = 3` - Voice message (`voice`)
* `const VIDEO = 4` - Video (`video`)
* `const DOCUMENT = 5` - Document (`document`)
* `const ENCRYPTED = 6` - Secret chat document (`encrypted`)
* `const TEMP = 7` - Temp document (`temp`)
* `const STICKER = 8` - Sticker (`sticker`)
* `const AUDIO = 9` - Music (`audio`)
* `const ANIMATION = 10` - GIF (`animation`)
* `const ENCRYPTED_THUMBNAIL = 11` - Thumbnail of secret chat document (`encrypted_thumbnail`)
* `const WALLPAPER = 12` - Wallpaper (`wallpaper`)
* `const VIDEO_NOTE = 13` - Round video (`video_note`)
* `const SECURE_RAW = 14` - Passport raw file (`secure_raw`)
* `const SECURE = 15` - Passport file (`secure`)
* `const WALLPAPER = 16` - Background (`background`)
* `const WALLPAPER = 17` - Size (`size`)
* `const NONE = 18` - 

### Bot API unique file ID types

The unique file type is a numeric constant indicating the type of the unique file ID, (the constant is always in the `danog\Decoder` namespace).
The unique file type name is a string version of the unique file type, typically the one used in bot API file objects.  


The `UNIQUE_TYPES` array contains a `unique file type` => `unique file type name` map.
The `UNIQUE_TYPES_IDS` array contains a `unique file type name` => `unique file type` map.
The `FULL_UNIQUE_MAP` array contains a `full file type` => `unique file type` map.

* `const UNIQUE_WEB = 0` - Used for web files (all file types that have a URL (`hasUrl`))
* `const UNIQUE_PHOTO = 1` - Used for photos and similar (`getType() <= PHOTO`)
* `const UNIQUE_DOCUMENT = 2` - Used for all other types of files (documents, audio, video, voice, sticker, animation, video note)
* `const UNIQUE_SECURE = 3` - Used for passport files
* `const UNIQUE_ENCRYPTED = 4` - Used for secret chat files
* `const UNIQUE_TEMP = 5` - Used for temp files

## Full API documentation

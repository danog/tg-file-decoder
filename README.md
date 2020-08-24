# tg-file-decoder

[![Build Status](https://travis-ci.org/danog/tg-file-decoder.svg?branch=master)](https://travis-ci.org/danog/tg-file-decoder) [![Build status](https://ci.appveyor.com/api/projects/status/akmq8k33ojdn5vf0?svg=true)](https://ci.appveyor.com/project/danog/tg-file-decoder)


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

* [FileId](#fileid) - Main file ID class
    * [fromBotAPI](#frombotapi) 
    * [getBotAPI](#getbotapi)
    * [getUnique](#getunique)
    * [getVersion](#getversion)
    * [setVersion](#setversion)
    * [getSubVersion](#getsubversion)
    * [setSubVersion](#setsubversion)
    * [getType](#gettype)
    * [getTypeName](#gettypename)
    * [setType](#settype)
    * [getFileReference](#getfilereference)
    * [setFileReference](#setfilereference)
    * [hasFileReference](#hasfilereference)
    * [getUrl](#geturl)
    * [hasUrl](#hasurl)
    * [setUrl](#seturl)
    * [getId](#getid)
    * [setId](#setid)
    * [hasId](#hasid)
    * [getAccessHash](#getaccesshash)
    * [setAccessHash](#setaccesshash)
    * [getVolumeId](#getvolumeid)
    * [setVolumeId](#setvolumeid)
    * [hasVolumeId](#hasvolumeid)
    * [getLocalId](#getlocalid)
    * [setLocalId](#setlocalid)
    * [hasLocalId](#haslocalid)
    * [getPhotoSizeSource](#getphotosizesource)
    * [setPhotoSizeSource](#setphotosizesource)
    * [hasPhotoSizeSource](#hasphotosizesource)
    * [getDcId](#getdcid)
    * [setDcId](#setdcid)
* [PhotoSizeSourceDialogPhoto](#photosizesourcedialogphoto)
    * [setType](#settype-1)
    * [getDialogId](#getdialogid)
    * [setDialogId](#setdialogid)
    * [getDialogAccessHash](#getdialogaccesshash)
    * [setDialogAccessHash](#setdialogaccesshash)
    * [isSmallDialogPhoto](#issmalldialogphoto)
    * [setDialogPhotoSmall](#setdialogphotosmall)
* [PhotoSizeSourceLegacy](#photosizesourcelegacy)
    * [setType](#settype-2)
    * [getSecret](#getsecret)
    * [setSecret](#setsecret)
* [PhotoSizeSourceStickersetThumbnail](#photosizesourcestickersetthumbnail)
    * [setType](#settype-3)
    * [getStickerSetId](#getstickersetid)
    * [setStickerSetId](#setstickersetid)
    * [getStickerSetAccessHash](#getstickersetaccesshash)
    * [setStickerSetAccessHash](#setstickersetaccesshash)
* [PhotoSizeSourceThumbnail](#photosizesourcethumbnail)
    * [setType](#settype-4)
    * [getThumbFileType](#getthumbfiletype)
    * [getThumbFileTypeString](#getthumbfiletypestring)
    * [setThumbFileType](#setthumbfiletype)
    * [getThumbType](#getthumbtype)
    * [setThumbType](#setthumbtype)
* [UniqueFileId](#uniquefileid)
    * [__toString](#__tostring-1)
    * [getUniqueBotAPI](#getuniquebotapi)
    * [fromUniqueBotAPI](#fromuniquebotapi)
    * [fromBotAPI](#frombotapi-1)
    * [fromFileId](#fromfileid)
    * [getTypeName](#gettypename-1)
    * [getType](#gettype-1)
    * [setType](#settype-5)
    * [getId](#getid-1)
    * [setId](#setid-1)
    * [hasId](#hasid-1)
    * [getVolumeId](#getvolumeid-1)
    * [setVolumeId](#setvolumeid-1)
    * [hasVolumeId](#hasvolumeid-1)
    * [getLocalId](#getlocalid-1)
    * [setLocalId](#setlocalid-1)
    * [hasLocalId](#haslocalid-1)
    * [getUrl](#geturl-1)
    * [setUrl](#seturl-1)
    * [hasUrl](#hasurl-1)

## FileId

Represents decoded bot API file ID.


* Full name: \danog\Decoder\FileId


### fromBotAPI

Decode file ID from bot API file ID.

```php
FileId::fromBotAPI( string $fileId ): self
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fileId` | **string** | File ID |




---

### getBotAPI

Get bot API file ID.

```php
FileId::getBotAPI(  ): string
```







---

### getUnique

Get unique file ID from file ID.

```php
FileId::getUnique(  ): \danog\Decoder\UniqueFileId
```







---

### __toString

Get bot API file ID.

```php
FileId::__toString(  ): string
```







---

### getVersion

Get bot API file ID version.

```php
FileId::getVersion(  ): integer
```







---

### setVersion

Set bot API file ID version.

```php
FileId::setVersion( integer $_version ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_version` | **integer** | Bot API file ID version. |




---

### getSubVersion

Get bot API file ID subversion.

```php
FileId::getSubVersion(  ): integer
```







---

### setSubVersion

Set bot API file ID subversion.

```php
FileId::setSubVersion( integer $_subVersion ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_subVersion` | **integer** | Bot API file ID subversion. |




---

### getType

Get file type.

```php
FileId::getType(  ): integer
```







---

### getTypeName

Get file type as string.

```php
FileId::getTypeName(  ): string
```







---

### setType

Set file type.

```php
FileId::setType( integer $_type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_type` | **integer** | File type. |




---

### getFileReference

Get file reference.

```php
FileId::getFileReference(  ): string
```







---

### setFileReference

Set file reference.

```php
FileId::setFileReference( string $_fileReference ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_fileReference` | **string** | File reference. |




---

### hasFileReference

Check if has file reference.

```php
FileId::hasFileReference(  ): boolean
```







---

### getUrl

Get file URL for weblocation.

```php
FileId::getUrl(  ): string
```







---

### hasUrl

Check if has file URL.

```php
FileId::hasUrl(  ): boolean
```







---

### setUrl

Set file URL for weblocation.

```php
FileId::setUrl( string $_url ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_url` | **string** | File URL for weblocation. |




---

### getId

Get file id.

```php
FileId::getId(  ): integer
```







---

### setId

Set file id.

```php
FileId::setId( integer $_id ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_id` | **integer** | File id. |




---

### hasId

Check if has file id.

```php
FileId::hasId(  ): boolean
```







---

### getAccessHash

Get file access hash.

```php
FileId::getAccessHash(  ): integer
```







---

### setAccessHash

Set file access hash.

```php
FileId::setAccessHash( integer $_accessHash ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_accessHash` | **integer** | File access hash. |




---

### getVolumeId

Get photo volume ID.

```php
FileId::getVolumeId(  ): integer
```







---

### setVolumeId

Set photo volume ID.

```php
FileId::setVolumeId( integer $_volumeId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_volumeId` | **integer** | Photo volume ID. |




---

### hasVolumeId

Check if has volume ID.

```php
FileId::hasVolumeId(  ): boolean
```







---

### getLocalId

Get photo local ID.

```php
FileId::getLocalId(  ): integer
```







---

### setLocalId

Set photo local ID.

```php
FileId::setLocalId( integer $_localId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_localId` | **integer** | Photo local ID. |




---

### hasLocalId

Check if has local ID.

```php
FileId::hasLocalId(  ): boolean
```







---

### getPhotoSizeSource

Get photo size source.

```php
FileId::getPhotoSizeSource(  ): \danog\Decoder\PhotoSizeSource
```







---

### setPhotoSizeSource

Set photo size source.

```php
FileId::setPhotoSizeSource( \danog\Decoder\PhotoSizeSource $_photoSizeSource ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_photoSizeSource` | **\danog\Decoder\PhotoSizeSource** | Photo size source. |




---

### hasPhotoSizeSource

Check if has photo size source.

```php
FileId::hasPhotoSizeSource(  ): boolean
```







---

### getDcId

Get dC ID.

```php
FileId::getDcId(  ): integer
```







---

### setDcId

Set dC ID.

```php
FileId::setDcId( integer $_dcId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_dcId` | **integer** | DC ID. |




---

## PhotoSizeSourceDialogPhoto

Represents source of photosize.



* Full name: \danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto
* Parent class: \danog\Decoder\PhotoSizeSource


### setType

Get photosize source type.

```php
PhotoSizeSourceDialogPhoto::setType( integer $type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **integer** | Type |




---

### getDialogId

Get dialog ID.

```php
PhotoSizeSourceDialogPhoto::getDialogId(  ): integer
```







---

### setDialogId

Set dialog ID.

```php
PhotoSizeSourceDialogPhoto::setDialogId( integer $id ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$id` | **integer** | Dialog ID |




---

### getDialogAccessHash

Get access hash of dialog.

```php
PhotoSizeSourceDialogPhoto::getDialogAccessHash(  ): integer
```







---

### setDialogAccessHash

Set access hash of dialog.

```php
PhotoSizeSourceDialogPhoto::setDialogAccessHash( integer $dialogAccessHash ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$dialogAccessHash` | **integer** | Access hash of dialog |




---

### isSmallDialogPhoto

Get whether the big or small version of the photo is being used.

```php
PhotoSizeSourceDialogPhoto::isSmallDialogPhoto(  ): boolean
```







---

### setDialogPhotoSmall

Set whether the big or small version of the photo is being used.

```php
PhotoSizeSourceDialogPhoto::setDialogPhotoSmall( boolean $_dialogPhotoSmall ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_dialogPhotoSmall` | **boolean** | Whether the big or small version of the photo is being used |




---

## PhotoSizeSourceLegacy

Represents source of photosize.



* Full name: \danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy
* Parent class: \danog\Decoder\PhotoSizeSource


### setType

Get photosize source type.

```php
PhotoSizeSourceLegacy::setType( integer $type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **integer** | Type |




---

### getSecret

Get secret legacy ID.

```php
PhotoSizeSourceLegacy::getSecret(  ): integer
```







---

### setSecret

Set secret legacy ID.

```php
PhotoSizeSourceLegacy::setSecret( integer $_secret ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_secret` | **integer** | Secret legacy ID |




---

## PhotoSizeSourceStickersetThumbnail

Represents source of photosize.



* Full name: \danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnail
* Parent class: \danog\Decoder\PhotoSizeSource


### setType

Get photosize source type.

```php
PhotoSizeSourceStickersetThumbnail::setType( integer $type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **integer** | Type |




---

### getStickerSetId

Get stickerset ID.

```php
PhotoSizeSourceStickersetThumbnail::getStickerSetId(  ): integer
```







---

### setStickerSetId

Set stickerset ID.

```php
PhotoSizeSourceStickersetThumbnail::setStickerSetId( integer $_stickerSetId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_stickerSetId` | **integer** | Stickerset ID |




---

### getStickerSetAccessHash

Get stickerset access hash.

```php
PhotoSizeSourceStickersetThumbnail::getStickerSetAccessHash(  ): integer
```







---

### setStickerSetAccessHash

Set stickerset access hash.

```php
PhotoSizeSourceStickersetThumbnail::setStickerSetAccessHash( integer $_stickerSetAccessHash ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_stickerSetAccessHash` | **integer** | Stickerset access hash |




---

## PhotoSizeSourceThumbnail

Represents source of photosize.



* Full name: \danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail
* Parent class: \danog\Decoder\PhotoSizeSource


### setType

Get photosize source type.

```php
PhotoSizeSourceThumbnail::setType( integer $type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **integer** | Type |




---


### getThumbFileType

Get file type of original file.

```php
PhotoSizeSourceThumbnail::getThumbFileType(  ): integer
```







---

### getThumbFileTypeString

Get file type of original file as string.

```php
PhotoSizeSourceThumbnail::getThumbFileTypeString(  ): string
```







---

### setThumbFileType

Set file type of original file.

```php
PhotoSizeSourceThumbnail::setThumbFileType( integer $_thumbFileType ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_thumbFileType` | **integer** | File type of original file |




---

### getThumbType

Get thumbnail size.

```php
PhotoSizeSourceThumbnail::getThumbType(  ): string
```







---

### setThumbType

Set thumbnail size.

```php
PhotoSizeSourceThumbnail::setThumbType( string $_thumbType ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_thumbType` | **string** | Thumbnail size |




---

## UniqueFileId

Represents decoded unique bot API file ID.



* Full name: \danog\Decoder\UniqueFileId


### __toString

Get unique bot API file ID.

```php
UniqueFileId::__toString(  ): string
```







---

### getUniqueBotAPI

Get unique bot API file ID.

```php
UniqueFileId::getUniqueBotAPI(  ): string
```







---

### fromUniqueBotAPI

Decode unique bot API file ID.

```php
UniqueFileId::fromUniqueBotAPI( string $fileId ): self
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fileId` | **string** | Bot API file ID |




---

### fromBotAPI

Convert full bot API file ID to unique file ID.

```php
UniqueFileId::fromBotAPI( string $fileId ): self
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fileId` | **string** | Full bot API file ID |




---

### fromFileId

Turn full file ID into unique file ID.

```php
UniqueFileId::fromFileId( \danog\Decoder\FileId $fileId ): self
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fileId` | **\danog\Decoder\FileId** | Full file ID |




---

### getTypeName

Get unique file type as string.

```php
UniqueFileId::getTypeName(  ): string
```







---

### getType

Get unique file type.

```php
UniqueFileId::getType(  ): integer
```







---

### setType

Set file type.

```php
UniqueFileId::setType( integer $_type ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_type` | **integer** | File type. |




---

### getId

Get file ID.

```php
UniqueFileId::getId(  ): integer
```







---

### setId

Set file ID.

```php
UniqueFileId::setId( integer $_id ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_id` | **integer** | File ID. |




---

### hasId

Check if has ID.

```php
UniqueFileId::hasId(  ): boolean
```







---

### getVolumeId

Get photo volume ID.

```php
UniqueFileId::getVolumeId(  ): integer
```







---

### setVolumeId

Set photo volume ID.

```php
UniqueFileId::setVolumeId( integer $_volumeId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_volumeId` | **integer** | Photo volume ID. |




---

### hasVolumeId

Check if has volume ID.

```php
UniqueFileId::hasVolumeId(  ): boolean
```







---

### getLocalId

Get photo local ID.

```php
UniqueFileId::getLocalId(  ): integer
```







---

### setLocalId

Set photo local ID.

```php
UniqueFileId::setLocalId( integer $_localId ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_localId` | **integer** | Photo local ID. |




---

### hasLocalId

Check if has local ID.

```php
UniqueFileId::hasLocalId(  ): boolean
```







---

### getUrl

Get weblocation URL.

```php
UniqueFileId::getUrl(  ): string
```







---

### setUrl

Set weblocation URL.

```php
UniqueFileId::setUrl( string $_url ): self
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$_url` | **string** | Weblocation URL |




---

### hasUrl

Check if has weblocation URL.

```php
UniqueFileId::hasUrl(  ): boolean
```










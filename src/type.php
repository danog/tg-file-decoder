<?php
/**
 * Type enum + helper functions.
 *
 * This file is part of tg-file-decoder.
 * tg-file-decoder is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * tg-file-decoder is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with tg-file-decoder.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2019 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://github.com/tg-file-decoder Documentation
 */

namespace danog\Decoder;

/**
 * Thumbnail.
 */
const THUMBNAIL = 0;
/**
 * Profile photo.
 * Used for users and channels, chat photos are normal PHOTOs.
 */
const PROFILE_PHOTO = 1;
/**
 * Normal photos.
 */
const PHOTO = 2;

/**
 * Voice messages.
 */
const VOICE = 3;
/**
 * Video.
 */
const VIDEO = 4;
/**
 * Document.
 */
const DOCUMENT = 5;
/**
 * Secret chat document.
 */
const ENCRYPTED = 6;
/**
 * Temporary document.
 */
const TEMP = 7;
/**
 * Sticker.
 */
const STICKER = 8;
/**
 * Music.
 */
const AUDIO = 9;
/**
 * GIF.
 */
const ANIMATION = 10;
/**
 * Encrypted thumbnail.
 */
const ENCRYPTED_THUMBNAIL = 11;
/**
 * Wallpaper.
 */
const WALLPAPER = 12;
/**
 * Round video.
 */
const VIDEO_NOTE = 13;
/**
 * Passport raw file.
 */
const SECURE_RAW = 14;
/**
 * Passport file.
 */
const SECURE = 15;
/**
 * Background.
 */
const BACKGROUND = 16;
/**
 * Size.
 */
const SIZE = 17;
const NONE = 18;

const TYPES = [
    THUMBNAIL => 'thumbnail',
    PROFILE_PHOTO => 'profile_photo',
    PHOTO => 'photo',
    VOICE => 'voice',
    VIDEO => 'video',
    DOCUMENT => 'document',
    ENCRYPTED => 'encrypted',
    TEMP => 'temp',
    STICKER => 'sticker',
    AUDIO => 'audio',
    ANIMATION => 'animation',
    ENCRYPTED_THUMBNAIL => 'encrypted_thumbnail',
    WALLPAPER => 'wallpaper',
    VIDEO_NOTE => 'video_note',
    SECURE_RAW => 'secure_raw',
    SECURE => 'secure',
    BACKGROUND => 'background',
    SIZE => 'size'
];
const TYPES_IDS = [
    'thumbnail' => THUMBNAIL,
    'profile_photo' => PROFILE_PHOTO,
    'photo' => PHOTO,
    'voice' => VOICE,
    'video' => VIDEO,
    'document' => DOCUMENT,
    'encrypted' => ENCRYPTED,
    'temp' => TEMP,
    'sticker' => STICKER,
    'audio' => AUDIO,
    'animation' => ANIMATION,
    'encrypted_thumbnail' => ENCRYPTED_THUMBNAIL,
    'wallpaper' => WALLPAPER,
    'video_note' => VIDEO_NOTE,
    'secure_raw' => SECURE_RAW,
    'secure' => SECURE,
    'background' => BACKGROUND,
    'size' => SIZE
];

const UNIQUE_WEB = 0;
const UNIQUE_PHOTO = 1;
const UNIQUE_DOCUMENT = 2;
const UNIQUE_SECURE = 3;
const UNIQUE_ENCRYPTED = 4;
const UNIQUE_TEMP = 5;

const UNIQUE_TYPES = [
    UNIQUE_WEB => 'web',
    UNIQUE_PHOTO => 'photo',
    UNIQUE_DOCUMENT => 'document',
    UNIQUE_SECURE => 'secure',
    UNIQUE_ENCRYPTED => 'encrypted',
    UNIQUE_TEMP => 'temp'
];

const UNIQUE_TYPES_IDS = [
    'web' => UNIQUE_WEB,
    'photo' => UNIQUE_PHOTO,
    'document' => UNIQUE_DOCUMENT,
    'secure' => UNIQUE_SECURE,
    'encrypted' => UNIQUE_ENCRYPTED,
    'temp' => UNIQUE_TEMP
];

const FULL_UNIQUE_MAP = [
    PHOTO => UNIQUE_PHOTO,
    PROFILE_PHOTO => UNIQUE_PHOTO,
    THUMBNAIL => UNIQUE_PHOTO,
    ENCRYPTED_THUMBNAIL => UNIQUE_PHOTO,
    WALLPAPER => UNIQUE_PHOTO,

    VIDEO => UNIQUE_DOCUMENT,
    VOICE => UNIQUE_DOCUMENT,
    DOCUMENT => UNIQUE_DOCUMENT,
    STICKER => UNIQUE_DOCUMENT,
    AUDIO => UNIQUE_DOCUMENT,
    ANIMATION => UNIQUE_DOCUMENT,
    VIDEO_NOTE => UNIQUE_DOCUMENT,
    BACKGROUND => UNIQUE_DOCUMENT,

    SECURE => UNIQUE_SECURE,
    SECURE_RAW => UNIQUE_SECURE,

    ENCRYPTED => UNIQUE_ENCRYPTED,

    TEMP => UNIQUE_TEMP
];

const PHOTOSIZE_SOURCE_LEGACY = 0;
const PHOTOSIZE_SOURCE_THUMBNAIL = 1;
const PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL = 2;
const PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG = 3;
const PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL = 4;
const PHOTOSIZE_SOURCE_FULL_LEGACY = 5;
const PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY = 6;
const PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG_LEGACY = 7;
const PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_LEGACY = 8;
const PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_VERSION = 9;

const WEB_LOCATION_FLAG =  1 << 24;
const FILE_REFERENCE_FLAG = 1 << 25;
const LONG = PHP_INT_SIZE === 8 ? 'Q' : 'l2';

$BIG_ENDIAN = \pack('L', 1) === \pack('N', 1);

/**
 * Unpack long properly, returns an actual number in any case.
 *
 * @param string $field Field to unpack
 *
 * @return string|int
 */
function unpackLong(string $field)
{
    if (PHP_INT_SIZE === 8) {
        /** @psalm-suppress InvalidGlobal */
        global $BIG_ENDIAN; // Evil
        return \unpack('q', $BIG_ENDIAN ? \strrev($field) : $field)[1];
    }
    if (\class_exists(\tgseclib\Math\BigInteger::class)) {
        return (string) new \tgseclib\Math\BigInteger(\strrev($field), -256);
    }
    if (\class_exists(\phpseclib\Math\BigInteger::class)) {
        return (string) new \phpseclib\Math\BigInteger(\strrev($field), -256);
    }
    throw new \Error('Please install phpseclib to unpack bot API file IDs');
}
/**
 * Pack string long.
 *
 * @param string|int $field
 *
 * @return string
 */
function packLongBig($field): string
{
    if (PHP_INT_SIZE === 8) {
        /** @psalm-suppress InvalidGlobal */
        global $BIG_ENDIAN; // Evil
        $res = \pack('q', $field);
        return $BIG_ENDIAN ? \strrev($res) : $res;
    }

    if (\class_exists(\tgseclib\Math\BigInteger::class)) {
        return (new \tgseclib\Math\BigInteger($field))->toBytes();
    }
    if (\class_exists(\phpseclib\Math\BigInteger::class)) {
        return (new \phpseclib\Math\BigInteger($field))->toBytes();
    }
    throw new \Error('Please install phpseclib to unpack bot API file IDs');
}
/**
 * Fix long parameters in case of 32 bit systems.
 *
 * @param array  $params Parameters
 * @param string $field  64-bit field
 *
 * @return void
 */
function fixLong(array &$params, string $field)
{
    if (PHP_INT_SIZE === 8) {
        return;
    }
    $params[$field] = [
        $params[$field.'1'],
        $params[$field.'2'],
    ];
    unset($params[$field.'1'], $params[$field.'2']);
}

/**
 * Encode long to string.
 *
 * @param string|int|int[] $fields Fields to encode
 *
 * @return string
 */
function packLong($fields): string
{
    if (\is_string($fields)) { // Already encoded, we hope
        return $fields;
    }
    if (PHP_INT_SIZE === 8) {
        return \pack(LONG, $fields);
    }
    return \pack(LONG, ...$fields);
}

/**
 * Base64URL decode.
 *
 * @param string $data Data to decode
 *
 * @internal
 *
 * @return string
 */
function base64urlDecode(string $data): string
{
    return \base64_decode(\str_pad(\strtr($data, '-_', '+/'), \strlen($data) % 4, '=', STR_PAD_RIGHT));
}

/**
 * Base64URL encode.
 *
 * @param string $data Data to encode
 *
 * @internal
 *
 * @return string
 */
function base64urlEncode(string $data): string
{
    return \rtrim(\strtr(\base64_encode($data), '+/', '-_'), '=');
}

/**
 * Null-byte RLE decode.
 *
 * @param string $string Data to decode
 *
 * @internal
 *
 * @return string
 */
function rleDecode(string $string): string
{
    $new = '';
    $last = '';
    $null = "\0";
    foreach (\str_split($string) as $cur) {
        if ($last === $null) {
            $new .= \str_repeat($last, \ord($cur));
            $last = '';
        } else {
            $new .= $last;
            $last = $cur;
        }
    }
    $string = $new.$last;

    return $string;
}

/**
 * Null-byte RLE encode.
 *
 * @param string $string Data to encode
 *
 * @internal
 *
 * @return string
 */
function rleEncode(string $string): string
{
    $new = '';
    $count = 0;
    $null = "\0";
    foreach (\str_split($string) as $cur) {
        if ($cur === $null) {
            ++$count;
        } else {
            if ($count > 0) {
                $new .= $null.\chr($count);
                $count = 0;
            }
            $new .= $cur;
        }
    }
    if ($count > 0) {
        $new .= $null.\chr($count);
        $count = 0;
    }

    return $new;
}


/**
 * Positive modulo
 * Works just like the % (modulus) operator, only returns always a postive number.
 *
 * @param int $a A
 * @param int $b B
 *
 * @internal
 *
 * @return int Modulo
 */
function posmod(int $a, int $b): int
{
    $resto = $a % $b;

    return $resto < 0 ? $resto + \abs($b) : $resto;
}

/**
 * Read TL string.
 *
 * @param mixed $stream Byte stream
 *
 * @internal
 *
 * @return string
 */
function readTLString($stream): string
{
    $l = \ord(\stream_get_contents($stream, 1));
    if ($l > 254) {
        throw new \InvalidArgumentException("Length too big!");
    }
    if ($l === 254) {
        $long_len = \unpack('V', \stream_get_contents($stream, 3).\chr(0))[1];
        $x = \stream_get_contents($stream, $long_len);
        $resto = posmod(-$long_len, 4);
        if ($resto > 0) {
            \stream_get_contents($stream, $resto);
        }
    } else {
        $x = $l ? \stream_get_contents($stream, $l) : '';
        $resto = posmod(-($l + 1), 4);
        if ($resto > 0) {
            \stream_get_contents($stream, $resto);
        }
    }
    return $x;
}

/**
 * Pack TL string.
 *
 * @param string $string String
 *
 * @return string
 */
function packTLString(string $string): string
{
    $l = \strlen($string);
    $concat = '';
    if ($l <= 253) {
        $concat .= \chr($l);
        $concat .= $string;
        $concat .= \pack('@'.posmod(-$l - 1, 4));
    } else {
        $concat .= \chr(254);
        $concat .= \substr(\pack('V', $l), 0, 3);
        $concat .= $string;
        $concat .= \pack('@'.posmod(-$l, 4));
    }
    return $concat;
}

/**
 * Internal decode function.
 *
 * I know that you will use this directly giuseppe
 *
 * @param string $fileId Bot API file ID
 *
 * @internal
 *
 * @return array
 */
function internalDecode(string $fileId): array
{
    $orig = $fileId;
    $fileId = rleDecode(base64urlDecode($fileId));
    $result = [];
    $result['version'] = \ord($fileId[\strlen($fileId) - 1]);
    $result['subVersion'] = $result['version'] === 4 ? \ord($fileId[\strlen($fileId) - 2]) : 0;

    $result += \unpack('VtypeId/Vdc_id', $fileId);
    $result['hasReference'] = (bool) ($result['typeId'] & FILE_REFERENCE_FLAG);
    $result['hasWebLocation'] = (bool) ($result['typeId'] & WEB_LOCATION_FLAG);
    $result['typeId'] &= ~FILE_REFERENCE_FLAG;
    $result['typeId'] &= ~WEB_LOCATION_FLAG;
    if (!isset(TYPES[$result['typeId']])) {
        throw new \InvalidArgumentException("Invalid file type provided: {$result['typeId']}");
    }
    $result['type'] = TYPES[$result['typeId']];
    $res = \fopen('php://memory', 'rw+b');
    \fwrite($res, \substr($fileId, 8));
    \fseek($res, 0);
    $fileId = $res;

    if ($result['hasReference']) {
        $result['fileReference'] = readTLString($fileId);
    }
    if ($result['hasWebLocation']) {
        $result['url'] = readTLString($fileId);
        $result['access_hash'] = \unpack(LONG.'access_hash', \stream_get_contents($fileId, 8));
        fixLong($result, 'access_hash');
        return $result;
    }

    $result += \unpack(LONG.'id/'.LONG.'access_hash', \stream_get_contents($fileId, 16));
    fixLong($result, 'id');
    fixLong($result, 'access_hash');

    if ($result['typeId'] <= PHOTO) {
        $parsePhotoSize = function () use (&$result, &$fileId) {
            $result['photosize_source'] = $result['subVersion'] >= 4 ? \unpack('V', \stream_get_contents($fileId, 4))[1] : 0;
            switch ($result['photosize_source']) {
                case PHOTOSIZE_SOURCE_LEGACY:
                    $result += \unpack(LONG.'secret', \stream_get_contents($fileId, 8));
                    fixLong($result, 'secret');
                    break;
                case PHOTOSIZE_SOURCE_THUMBNAIL:
                    $result += \unpack('Vfile_type/athumbnail_type', \stream_get_contents($fileId, 8));
                    break;
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL:
                    $result['photo_size'] = $result['photosize_source'] === PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL ? 'photo_small' : 'photo_big';
                    $result['dialog_id'] = unpackLong(\stream_get_contents($fileId, 8));
                    $result['dialog_access_hash'] = \unpack(LONG, \stream_get_contents($fileId, 8))[1];
                    fixLong($result, 'dialog_access_hash');
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL:
                    $result += \unpack(LONG.'sticker_set_id/'.LONG.'sticker_set_access_hash', \stream_get_contents($fileId, 16));
                    fixLong($result, 'sticker_set_id');
                    fixLong($result, 'sticker_set_access_hash');
                    break;

                case PHOTOSIZE_SOURCE_FULL_LEGACY:
                    $result += \unpack(LONG.'volume_id/'.LONG.'secret/llocal_id', \stream_get_contents($fileId, 20));
                    fixLong($result, 'volume_id');
                    fixLong($result, 'secret');
                    break;
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG_LEGACY:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY:
                    $result['photo_size'] = $result['photosize_source'] === PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY ? 'photo_small' : 'photo_big';
                    $result['dialog_id'] = unpackLong(\stream_get_contents($fileId, 8));
                    $result['dialog_access_hash'] = \unpack(LONG, \stream_get_contents($fileId, 8))[1];
                    fixLong($result, 'dialog_access_hash');

                    $result += \unpack(LONG.'volume_id/llocal_id', \stream_get_contents($fileId, 12));
                    fixLong($result, 'volume_id');
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_LEGACY:
                    $result += \unpack(LONG.'sticker_set_id/'.LONG.'sticker_set_access_hash', \stream_get_contents($fileId, 16));
                    fixLong($result, 'sticker_set_id');
                    fixLong($result, 'sticker_set_access_hash');

                    $result += \unpack(LONG.'volume_id/llocal_id', \stream_get_contents($fileId, 12));
                    fixLong($result, 'volume_id');
                    break;

                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_VERSION:
                    $result += \unpack(LONG.'sticker_set_id/'.LONG.'sticker_set_access_hash/lsticker_version', \stream_get_contents($fileId, 20));
                    fixLong($result, 'sticker_set_id');
                    fixLong($result, 'sticker_set_access_hash');
                    break;
            }
        };
        if ($result['subVersion'] >= 32) {
            $parsePhotoSize();
        } else {
            $result += \unpack(LONG.'volume_id', \stream_get_contents($fileId, 8));
            fixLong($result, 'volume_id');

            if ($result['subVersion'] >= 22) {
                $parsePhotoSize();
                $result += \unpack('llocal_id', \stream_get_contents($fileId, 4));
            } else {
                $result += \unpack(LONG.'secret/llocal_id', \stream_get_contents($fileId, 12));
                fixLong($result, 'volume_id');
                fixLong($result, 'secret');
            }
        }
    }
    $l = \fstat($fileId)['size'] - \ftell($fileId);
    $l -= $result['version'] >= 4 ? 2 : 1;
    if ($l > 0) {
        \trigger_error("File ID $orig has $l bytes of leftover data");
    }
    return $result;
}
/**
 * Internal decode function.
 *
 * I know that you will use this directly giuseppe
 *
 * @param string $fileId Bot API file ID
 *
 * @internal
 *
 * @return array
 */
function internalDecodeUnique(string $fileId): array
{
    $orig = $fileId;
    $fileId = rleDecode(base64urlDecode($fileId));


    $result = \unpack('VtypeId', $fileId);
    if (!isset(UNIQUE_TYPES[$result['typeId']])) {
        throw new \InvalidArgumentException("Invalid file type provided: {$result['typeId']}");
    }
    $result['type'] = UNIQUE_TYPES[$result['typeId']];

    $fileId = \substr($fileId, 4);
    if ($result['typeId'] === UNIQUE_WEB) {
        $res = \fopen('php://memory', 'rw+b');
        \fwrite($res, $fileId);
        \fseek($res, 0);
        $fileId = $res;
        $result['url'] = readTLString($fileId);

        $l = \fstat($fileId)['size'] - \ftell($fileId);
    } elseif (\strlen($fileId) === 12) {
        // Legacy photos
        $result += \unpack(LONG.'volume_id/llocal_id', $fileId);
        fixLong($result, 'volume_id');

        $l = 0;
    } elseif (\strlen($fileId) === 9) {
        // Dialog photos/thumbnails
        $result += \unpack(LONG.'id/CsubType', $fileId);
        fixLong($result, 'id');

        $l = 0;
    } elseif (\strlen($fileId) === 13) {
        // Stickerset ID/version
        $result += \unpack('CsubType/'.LONG.'sticker_set_id/lsticker_set_version', $fileId);
        fixLong($result, 'sticker_set_id');

        $l = 0;
    } elseif (\strlen($fileId) === 8) {
        // Any other document
        $result += \unpack(LONG.'id', $fileId);
        fixLong($result, 'id');

        $l = 0;
    } else {
        $l = \strlen($fileId);
    }
    if ($l > 0) {
        \trigger_error("Unique file ID $orig has $l bytes of leftover data");
    }

    return $result;
}

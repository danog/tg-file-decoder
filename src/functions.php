<?php declare(strict_types=1);

namespace danog\Decoder;

const WEB_LOCATION_FLAG =  1 << 24;
const FILE_REFERENCE_FLAG = 1 << 25;
const LONG = PHP_INT_SIZE === 8 ? 'Q' : 'l2';

/** @psalm-suppress UnusedVariable */
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
 *
 */
function packLongBig(string|int $field): string
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
 */
function packLong(string|int|array $fields): string
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
 */
function readTLString(mixed $stream): string
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
            \fseek($stream, $resto, SEEK_CUR);
        }
    } else {
        $x = $l ? \stream_get_contents($stream, $l) : '';
        $resto = posmod(-($l + 1), 4);
        if ($resto > 0) {
            \fseek($stream, $resto, SEEK_CUR);
        }
    }
    \assert($x !== false);
    return $x;
}

/**
 * Pack TL string.
 *
 * @param string $string String
 *
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
    $result['type'] = FileIdType::from($result['typeId']);
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

    if ($result['typeId'] <= FileIdType::PHOTO->value) {
        $parsePhotoSize = function () use (&$result, &$fileId) {
            $result['photosize_source'] = $result['subVersion'] >= 4 ? \unpack('V', \stream_get_contents($fileId, 4))[1] : 0;
            switch ($result['photosize_source']) {
                case PhotoSizeSourceType::LEGACY:
                    $result += \unpack(LONG.'secret', \stream_get_contents($fileId, 8));
                    fixLong($result, 'secret');
                    break;
                case PhotoSizeSourceType::THUMBNAIL:
                    $result += \unpack('Vfile_type/athumbnail_type', \stream_get_contents($fileId, 8));
                    break;
                case PhotoSizeSourceType::DIALOGPHOTO_BIG:
                case PhotoSizeSourceType::DIALOGPHOTO_SMALL:
                    $result['photo_size'] = $result['photosize_source'] === PhotoSizeSourceType::DIALOGPHOTO_SMALL ? 'photo_small' : 'photo_big';
                    $result['dialog_id'] = unpackLong(\stream_get_contents($fileId, 8));
                    $result['dialog_access_hash'] = \unpack(LONG, \stream_get_contents($fileId, 8))[1];
                    fixLong($result, 'dialog_access_hash');
                    break;
                case PhotoSizeSourceType::STICKERSET_THUMBNAIL:
                    $result += \unpack(LONG.'sticker_set_id/'.LONG.'sticker_set_access_hash', \stream_get_contents($fileId, 16));
                    fixLong($result, 'sticker_set_id');
                    fixLong($result, 'sticker_set_access_hash');
                    break;

                case PhotoSizeSourceType::FULL_LEGACY:
                    $result += \unpack(LONG.'volume_id/'.LONG.'secret/llocal_id', \stream_get_contents($fileId, 20));
                    fixLong($result, 'volume_id');
                    fixLong($result, 'secret');
                    break;
                case PhotoSizeSourceType::DIALOGPHOTO_BIG_LEGACY:
                case PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY:
                    $result['photo_size'] = $result['photosize_source'] === PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY ? 'photo_small' : 'photo_big';
                    $result['dialog_id'] = unpackLong(\stream_get_contents($fileId, 8));
                    $result['dialog_access_hash'] = \unpack(LONG, \stream_get_contents($fileId, 8))[1];
                    fixLong($result, 'dialog_access_hash');

                    $result += \unpack(LONG.'volume_id/llocal_id', \stream_get_contents($fileId, 12));
                    fixLong($result, 'volume_id');
                    break;
                case PhotoSizeSourceType::STICKERSET_THUMBNAIL_LEGACY:
                    $result += \unpack(LONG.'sticker_set_id/'.LONG.'sticker_set_access_hash', \stream_get_contents($fileId, 16));
                    fixLong($result, 'sticker_set_id');
                    fixLong($result, 'sticker_set_access_hash');

                    $result += \unpack(LONG.'volume_id/llocal_id', \stream_get_contents($fileId, 12));
                    fixLong($result, 'volume_id');
                    break;

                case PhotoSizeSourceType::STICKERSET_THUMBNAIL_VERSION:
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
 */
function internalDecodeUnique(string $fileId): array
{
    $orig = $fileId;
    $fileId = rleDecode(base64urlDecode($fileId));

    $result = \unpack('VtypeId', $fileId);
    $result['type'] = UniqueFileIdType::from($result['typeId']);

    $fileId = \substr($fileId, 4);
    if ($result['typeId'] === UniqueFileIdType::WEB) {
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

    \assert($result !== false);
    return $result;
}

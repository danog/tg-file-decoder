<?php declare(strict_types=1);
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

use AssertionError;

enum FileIdType: int
{
    /**
     * Thumbnail.
     */
    case THUMBNAIL = 0;
    /**
     * Profile photo.
     * Used for users and channels, chat photos are normal PHOTOs.
     */
    case PROFILE_PHOTO = 1;
    /**
     * Normal photos.
     */
    case PHOTO = 2;

    /**
     * Voice messages.
     */
    case VOICE = 3;
    /**
     * Video.
     */
    case VIDEO = 4;
    /**
     * Document.
     */
    case DOCUMENT = 5;
    /**
     * Secret chat document.
     */
    case ENCRYPTED = 6;
    /**
     * Temporary document.
     */
    case TEMP = 7;
    /**
     * Sticker.
     */
    case STICKER = 8;
    /**
     * Music.
     */
    case AUDIO = 9;
    /**
     * GIF.
     */
    case ANIMATION = 10;
    /**
     * Encrypted thumbnail.
     */
    case ENCRYPTED_THUMBNAIL = 11;
    /**
     * Wallpaper.
     */
    case WALLPAPER = 12;
    /**
     * Round video.
     */
    case VIDEO_NOTE = 13;
    /**
     * Passport raw file.
     */
    case SECURE_RAW = 14;
    /**
     * Passport file.
     */
    case SECURE = 15;
    /**
     * Background.
     */
    case BACKGROUND = 16;
    /**
     * Size.
     */
    case SIZE = 17;
    case NONE = 18;

    /**
     * Convert file ID type to unique file ID type.
     */
    public function toUnique(): UniqueFileIdType
    {
        return match ($this) {
            self::PHOTO => UniqueFileIdType::PHOTO,
            self::PROFILE_PHOTO => UniqueFileIdType::PHOTO,
            self::THUMBNAIL => UniqueFileIdType::PHOTO,
            self::ENCRYPTED_THUMBNAIL => UniqueFileIdType::PHOTO,
            self::WALLPAPER => UniqueFileIdType::PHOTO,

            self::VIDEO => UniqueFileIdType::DOCUMENT,
            self::VOICE => UniqueFileIdType::DOCUMENT,
            self::DOCUMENT => UniqueFileIdType::DOCUMENT,
            self::STICKER => UniqueFileIdType::DOCUMENT,
            self::AUDIO => UniqueFileIdType::DOCUMENT,
            self::ANIMATION => UniqueFileIdType::DOCUMENT,
            self::VIDEO_NOTE => UniqueFileIdType::DOCUMENT,
            self::BACKGROUND => UniqueFileIdType::DOCUMENT,

            self::SECURE => UniqueFileIdType::SECURE,
            self::SECURE_RAW => UniqueFileIdType::SECURE,

            self::ENCRYPTED => UniqueFileIdType::ENCRYPTED,

            self::TEMP => UniqueFileIdType::TEMP,

            default => throw new AssertionError("Cannot convert file ID of type ".$this->name." to a unique file ID!")
        };
    }
}

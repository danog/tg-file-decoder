<?php
/**
 * Photosize source class.
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

use danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy;

/**
 * Represents source of photosize.
 *
 * @template T
 */
abstract class PhotoSizeSource
{
    /**
     * Source type.
     *
     * @var int
     */
    private $_type;

    /**
     * Set photosize source type.
     *
     * @param integer $type Type
     */
    public function __construct(int $type)
    {
        $this->_type = $type;

        return $this;
    }
    /**
     * Get photosize source type.
     *
     * @return integer
     *
     * @psalm-return (
     *     T is PhotoSizeSourceLegacy ?
     *     ? \danog\Decoder\PHOTOSIZE_SOURCE_LEGACY
     *     : (T is PhotoSizeSourceDialogPhoto
     *       ? \danog\Decoder\PHOTOSIZE_SOURCE_DIALOGPHOTO_*
     *       (T is PhotoSizeSourceStickersetThumbnail
     *         ? \danog\Decoder\PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL
     *         : \danog\Decoder\PHOTOSIZE_SOURCE_THUMBNAIL
     *       )
     *     )
     *
     * @internal Internal use
     */
    public function getType(): int
    {
        return $this->_type;
    }
}

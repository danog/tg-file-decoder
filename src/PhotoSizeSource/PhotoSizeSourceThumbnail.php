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

namespace danog\Decoder\PhotoSizeSource;

use danog\Decoder\PhotoSizeSource;

use const danog\Decoder\TYPES;

/**
 * Represents source of photosize.
 */
class PhotoSizeSourceThumbnail extends PhotoSizeSource
{
    /**
     * File type of original file.
     *
     * @var int
     */
    private $_thumbFileType;
    /**
     * Thumbnail size.
     *
     * @var string
     */
    private $_thumbType;

    /**
     * Get file type of original file.
     *
     * @return int
     */
    public function getThumbFileType(): int
    {
        return $this->_thumbFileType;
    }
    /**
     * Get file type of original file as string.
     *
     * @return string
     */
    public function getThumbFileTypeString(): string
    {
        return TYPES[$this->_thumbFileType];
    }

    /**
     * Set file type of original file.
     *
     * @param int $_thumbFileType File type of original file
     *
     * @return self
     */
    public function setThumbFileType(int $_thumbFileType): self
    {
        $this->_thumbFileType = $_thumbFileType;

        return $this;
    }

    /**
     * Get thumbnail size.
     *
     * @return string
     */
    public function getThumbType(): string
    {
        return $this->_thumbType;
    }

    /**
     * Set thumbnail size.
     *
     * @param string $_thumbType Thumbnail size
     *
     * @return self
     */
    public function setThumbType(string $_thumbType): self
    {
        $this->_thumbType = $_thumbType;

        return $this;
    }
}

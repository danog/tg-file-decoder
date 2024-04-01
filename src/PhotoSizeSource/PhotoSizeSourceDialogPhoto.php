<?php declare(strict_types=1);
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
use danog\Decoder\PhotoSizeSourceType;

/**
 * Represents source of photosize.
 *
 * @api
 *
 * @extends PhotoSizeSource<PhotoSizeSourceDialogPhoto>
 */
final class PhotoSizeSourceDialogPhoto extends PhotoSizeSource
{
    /**
     * ID of dialog.
     *
     */
    private int $dialogId;
    /**
     * Access hash of dialog.
     *
     */
    private int $dialogAccessHash;

    /**
     * Get dialog ID.
     *
     * @return int
     */
    public function getDialogId()
    {
        return $this->dialogId;
    }
    /**
     * Set dialog ID.
     *
     * @param int $id Dialog ID
     *
     */
    public function setDialogId(int $id): self
    {
        $this->dialogId = $id;
        return $this;
    }
    /**
     * Get access hash of dialog.
     *
     * @return int
     */
    public function getDialogAccessHash()
    {
        return $this->dialogAccessHash;
    }

    /**
     * Set access hash of dialog.
     *
     * @param int $dialogAccessHash Access hash of dialog
     *
     */
    public function setDialogAccessHash(int $dialogAccessHash): self
    {
        $this->dialogAccessHash = $dialogAccessHash;

        return $this;
    }

    /**
     * Get whether the big or small version of the photo is being used.
     *
     */
    public function isSmallDialogPhoto(): bool
    {
        return \in_array($this->getType(), [PhotoSizeSourceType::DIALOGPHOTO_SMALL_LEGACY, PhotoSizeSourceType::DIALOGPHOTO_SMALL], true);
    }
}

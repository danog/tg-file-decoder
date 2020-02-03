<?php
/**
 * Decoded UniqueFileId class.
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
 * Represents decoded unique bot API file ID.
 */
class UniqueFileId
{
    /**
     * File type.
     *
     * @var int
     */
    private $_type = NONE;
    /**
     * File ID.
     *
     * @var int
     */
    private $_id;
    /**
     * Photo volume ID.
     *
     * @var int
     */
    private $_volumeId;
    /**
     * Photo local ID.
     *
     * @var int
     */
    private $_localId;
    /**
     * Weblocation URL.
     *
     * @var string
     */
    private $_url;
    /**
     * Basic constructor function.
     */
    public function __construct()
    {
    }

    /**
     * Get unique bot API file ID.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUniqueBotAPI();
    }

    /**
     * Get unique bot API file ID.
     *
     * @return string
     */
    public function getUniqueBotAPI(): string
    {
        $fileId = \pack('V', $this->getType());
        if ($this->getType() === UNIQUE_WEB) {
            $fileId .= packTLString($this->getUrl());
        } elseif ($this->getType() === UNIQUE_PHOTO) {
            $fileId .= packLong($this->getVolumeId());
            $fileId .= \pack('l', $this->getLocalId());
        } elseif ($this->hasId()) {
            $fileId .= packLong($this->getId());
        }

        return base64urlEncode(rleEncode($fileId));
    }

    /**
     * Decode unique bot API file ID.
     *
     * @param string $fileId Bot API file ID
     *
     * @return self
     */
    public static function fromUniqueBotAPI(string $fileId): self
    {
        $result = new self();
        $resultArray = internalDecodeUnique($fileId);
        $result->setType($resultArray['typeId']);
        if ($result->getType() === UNIQUE_WEB) {
            $result->setUrl($resultArray['url']);
        } elseif ($result->getType() === UNIQUE_PHOTO) {
            $result->setVolumeId($resultArray['volume_id']);
            $result->setLocalId($resultArray['local_id']);
        } elseif (isset($resultArray['id'])) {
            $result->setId($resultArray['id']);
        }

        return $result;
    }

    /**
     * Convert full bot API file ID to unique file ID.
     *
     * @param string $fileId Full bot API file ID
     *
     * @return self
     */
    public static function fromBotAPI(string $fileId): self
    {
        return FileId::fromBotAPI($fileId)->getUnique();
    }

    /**
     * Turn full file ID into unique file ID.
     *
     * @param FileId $fileId Full file ID
     *
     * @return self
     */
    public static function fromFileId(FileId $fileId): self
    {
        $result = new self();
        $result->setType(FULL_UNIQUE_MAP[$fileId->getType()]);
        if ($result->hasUrl()) {
            $result->setType(UNIQUE_WEB);
        }
        if ($result->getType() === UNIQUE_WEB) {
            $result->setUrl($fileId->getUrl());
        } elseif ($result->getType() === UNIQUE_PHOTO) {
            $result->setVolumeId($fileId->getVolumeId());
            $result->setLocalId($fileId->getLocalId());
        } elseif ($fileId->hasId()) {
            $result->setId($fileId->getId());
        }

        return $result;
    }

    /**
     * Get unique file type as string.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return UNIQUE_TYPES[$this->_type];
    }

    /**
     * Get unique file type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->_type;
    }

    /**
     * Set file type.
     *
     * @param int $_type File type.
     *
     * @return self
     */
    public function setType(int $_type): self
    {
        $this->_type = $_type;

        return $this;
    }

    /**
     * Get file ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set file ID.
     *
     * @param int $_id File ID.
     *
     * @return self
     */
    public function setId($_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    /**
     * Check if has ID.
     *
     * @return boolean
     */
    public function hasId(): bool
    {
        return isset($this->_id);
    }


    /**
     * Get photo volume ID.
     *
     * @return int
     */
    public function getVolumeId()
    {
        return $this->_volumeId;
    }

    /**
     * Set photo volume ID.
     *
     * @param int $_volumeId Photo volume ID.
     *
     * @return self
     */
    public function setVolumeId($_volumeId): self
    {
        $this->_volumeId = $_volumeId;

        return $this;
    }
    /**
     * Check if has volume ID.
     *
     * @return boolean
     */
    public function hasVolumeId(): bool
    {
        return isset($this->_volumeId);
    }

    /**
     * Get photo local ID.
     *
     * @return int
     */
    public function getLocalId(): int
    {
        return $this->_localId;
    }

    /**
     * Set photo local ID.
     *
     * @param int $_localId Photo local ID.
     *
     * @return self
     */
    public function setLocalId(int $_localId): self
    {
        $this->_localId = $_localId;

        return $this;
    }

    /**
     * Check if has local ID.
     *
     * @return boolean
     */
    public function hasLocalId(): bool
    {
        return isset($this->_localId);
    }


    /**
     * Get weblocation URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * Set weblocation URL.
     *
     * @param string $_url Weblocation URL
     *
     * @return self
     */
    public function setUrl(string $_url): self
    {
        $this->_url = $_url;

        return $this;
    }

    /**
     * Check if has weblocation URL.
     *
     * @return boolean
     */
    public function hasUrl(): bool
    {
        return isset($this->_url);
    }
}

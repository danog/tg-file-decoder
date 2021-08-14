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

use danog\Decoder\PhotoSizeSource\PhotoSizeSourceDialogPhoto;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnailVersion;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;

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
     * Photo subtype
     *
     * @var int
     */
    private $_subType;
    /**
     * Sticker set ID
     *
     * @var int
     */
    private $_stickerSetId;
    /**
     * Sticker set version
     *
     * @var int
     */
    private $_stickerSetVersion;
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
            if ($this->hasVolumeId()) {
                $fileId .= packLong($this->getVolumeId());
                $fileId .= \pack('l', $this->getLocalId());
            } elseif ($this->hasStickerSetId()) {
                $fileId .= chr($this->getSubType());
                $fileId .= packLong($this->getStickerSetId());
                $fileId .= pack('l', $this->getStickerSetVersion());
            } else {
                $fileId .= packLong($this->getId());
                $fileId .= chr($this->getSubType());
            }
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
            if (isset($resultArray['volume_id'])) {
                $result->setVolumeId($resultArray['volume_id']);
                $result->setLocalId($resultArray['local_id']);
            } else if (isset($resultArray['id'])) {
                $result->setId($resultArray['id']);
                $result->setSubType($resultArray['subType']);
            } else if (isset($resultArray['sticker_set_id'])) {
                $result->setStickerSetId($resultArray['sticker_set_id']);
                $result->setStickerSetVersion($resultArray['sticker_set_version']);
                $result->setSubType($resultArray['subType']);
            }
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
            if ($fileId->hasVolumeId()) {
                $result->setVolumeId($fileId->getVolumeId());
                $result->setLocalId($fileId->getLocalId());
            } else if ($fileId->hasId()) {
                $result->setId($fileId->getId());
                $photoSize = $fileId->getPhotoSizeSource();
                if ($photoSize instanceof PhotoSizeSourceThumbnail) {
                    $type = $photoSize->getThumbType();
                    if ($type === 'a') {
                        $type = chr(0);
                    } else if ($type === 'c') {
                        $type = chr(1);
                    } else {
                        $type = chr(ord($type)+5);
                    }
                    $result->setSubType(ord($type));
                } else if ($photoSize instanceof PhotoSizeSourceDialogPhoto) {
                    $result->setSubType($photoSize->isSmallDialogPhoto() ? 0 : 1);
                } else if ($photoSize instanceof PhotoSizeSourceStickersetThumbnailVersion) {
                    $result->setSubType(2);
                    $result->setStickerSetId($photoSize->getStickerSetId());
                    $result->setStickerSetVersion($photoSize->getStickerSetVersion());
                }
            }
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

    /**
     * Get photo subtype
     *
     * @return int
     */
    public function getSubType(): int
    {
        return $this->_subType;
    }

    /**
     * Has photo subtype?
     *
     * @return bool
     */
    public function hasSubType(): bool
    {
        return isset($this->_subType);
    }

    /**
     * Set photo subtype
     *
     * @param int $_subType Photo subtype
     *
     * @return self
     */
    public function setSubType(int $_subType): self
    {
        $this->_subType = $_subType;

        return $this;
    }

    /**
     * Get sticker set ID
     *
     * @return int
     */
    public function getStickerSetId()
    {
        return $this->_stickerSetId;
    }


    /**
     * Has sticker set ID?
     *
     * @return bool
     */
    public function hasStickerSetId(): bool
    {
        return isset($this->_stickerSetId);
    }

    /**
     * Set sticker set ID
     *
     * @param int $_stickerSetId Sticker set ID
     *
     * @return self
     */
    public function setStickerSetId($_stickerSetId): self
    {
        $this->_stickerSetId = $_stickerSetId;

        return $this;
    }

    /**
     * Get sticker set version
     *
     * @return int
     */
    public function getStickerSetVersion(): int
    {
        return $this->_stickerSetVersion;
    }

    /**
     * Has sticker set version
     *
     * @return bool
     */
    public function hasStickerSetVersion(): bool
    {
        return isset($this->_stickerSetVersion);
    }

    /**
     * Set sticker set version
     *
     * @param int $_stickerSetVersion Sticker set version
     *
     * @return self
     */
    public function setStickerSetVersion(int $_stickerSetVersion): self
    {
        $this->_stickerSetVersion = $_stickerSetVersion;

        return $this;
    }
}

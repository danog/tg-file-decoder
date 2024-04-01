<?php declare(strict_types=1);
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
     */
    private int $type = NONE;
    /**
     * File ID.
     *
     */
    private int $id;
    /**
     * Photo volume ID.
     *
     */
    private int $volumeId;
    /**
     * Photo local ID.
     *
     */
    private int $localId;
    /**
     * Photo subtype.
     *
     */
    private int $subType;
    /**
     * Sticker set ID.
     *
     */
    private int $stickerSetId;
    /**
     * Sticker set version.
     *
     */
    private int $stickerSetVersion;
    /**
     * Weblocation URL.
     *
     */
    private string $url;
    /**
     * Basic constructor function.
     */
    public function __construct()
    {
    }

    /**
     * Get unique bot API file ID.
     *
     */
    public function __toString(): string
    {
        return $this->getUniqueBotAPI();
    }

    /**
     * Get unique bot API file ID.
     *
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
                $fileId .= \chr($this->getSubType());
                $fileId .= packLong($this->getStickerSetId());
                $fileId .= \pack('l', $this->getStickerSetVersion());
            } else {
                $fileId .= packLong($this->getId());
                $fileId .= \chr($this->getSubType());
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
            } elseif (isset($resultArray['id'])) {
                $result->setId($resultArray['id']);
                $result->setSubType($resultArray['subType']);
            } elseif (isset($resultArray['sticker_set_id'])) {
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
            } elseif ($fileId->hasId()) {
                $result->setId($fileId->getId());
                $photoSize = $fileId->getPhotoSizeSource();
                if ($photoSize instanceof PhotoSizeSourceThumbnail) {
                    $type = $photoSize->getThumbType();
                    if ($type === 'a') {
                        $type = \chr(0);
                    } elseif ($type === 'c') {
                        $type = \chr(1);
                    } else {
                        $type = \chr(\ord($type)+5);
                    }
                    $result->setSubType(\ord($type));
                } elseif ($photoSize instanceof PhotoSizeSourceDialogPhoto) {
                    $result->setSubType($photoSize->isSmallDialogPhoto() ? 0 : 1);
                } elseif ($photoSize instanceof PhotoSizeSourceStickersetThumbnailVersion) {
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
     */
    public function getTypeName(): string
    {
        return UNIQUE_TYPES[$this->type];
    }

    /**
     * Get unique file type.
     *
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Set file type.
     *
     * @param int $type File type.
     *
     */
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get file ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set file ID.
     *
     * @param int $id File ID.
     *
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Check if has ID.
     *
     * @return boolean
     */
    public function hasId(): bool
    {
        return isset($this->id);
    }

    /**
     * Get photo volume ID.
     *
     * @return int
     */
    public function getVolumeId()
    {
        return $this->volumeId;
    }

    /**
     * Set photo volume ID.
     *
     * @param int $volumeId Photo volume ID.
     *
     */
    public function setVolumeId(int $volumeId): self
    {
        $this->volumeId = $volumeId;

        return $this;
    }
    /**
     * Check if has volume ID.
     *
     * @return boolean
     */
    public function hasVolumeId(): bool
    {
        return isset($this->volumeId);
    }

    /**
     * Get photo local ID.
     *
     */
    public function getLocalId(): int
    {
        return $this->localId;
    }

    /**
     * Set photo local ID.
     *
     * @param int $localId Photo local ID.
     *
     */
    public function setLocalId(int $localId): self
    {
        $this->localId = $localId;

        return $this;
    }

    /**
     * Check if has local ID.
     *
     * @return boolean
     */
    public function hasLocalId(): bool
    {
        return isset($this->localId);
    }

    /**
     * Get weblocation URL.
     *
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set weblocation URL.
     *
     * @param string $url Weblocation URL
     *
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Check if has weblocation URL.
     *
     * @return boolean
     */
    public function hasUrl(): bool
    {
        return isset($this->url);
    }

    /**
     * Get photo subtype.
     *
     */
    public function getSubType(): int
    {
        return $this->subType;
    }

    /**
     * Has photo subtype?
     *
     */
    public function hasSubType(): bool
    {
        return isset($this->subType);
    }

    /**
     * Set photo subtype.
     *
     * @param int $subType Photo subtype
     *
     */
    public function setSubType(int $subType): self
    {
        $this->subType = $subType;

        return $this;
    }

    /**
     * Get sticker set ID.
     *
     * @return int
     */
    public function getStickerSetId()
    {
        return $this->stickerSetId;
    }

    /**
     * Has sticker set ID?
     *
     */
    public function hasStickerSetId(): bool
    {
        return isset($this->stickerSetId);
    }

    /**
     * Set sticker set ID.
     *
     * @param int $stickerSetId Sticker set ID
     *
     */
    public function setStickerSetId(int $stickerSetId): self
    {
        $this->stickerSetId = $stickerSetId;

        return $this;
    }

    /**
     * Get sticker set version.
     *
     */
    public function getStickerSetVersion(): int
    {
        return $this->stickerSetVersion;
    }

    /**
     * Has sticker set version.
     *
     */
    public function hasStickerSetVersion(): bool
    {
        return isset($this->stickerSetVersion);
    }

    /**
     * Set sticker set version.
     *
     * @param int $stickerSetVersion Sticker set version
     *
     */
    public function setStickerSetVersion(int $stickerSetVersion): self
    {
        $this->stickerSetVersion = $stickerSetVersion;

        return $this;
    }
}

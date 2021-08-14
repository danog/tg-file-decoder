<?php
/**
 * Decoded FileId class.
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
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceLegacy;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnail;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceStickersetThumbnailVersion;
use danog\Decoder\PhotoSizeSource\PhotoSizeSourceThumbnail;

/**
 * Represents decoded bot API file ID.
 */
class FileId
{
    /**
     * Bot API file ID version.
     *
     * @var int
     */
    private $_version = 4;

    /**
     * Bot API file ID subversion.
     *
     * @var int
     */
    private $_subVersion = 30;

    /**
     * DC ID.
     *
     * @var int
     */
    private $_dcId = 0;

    /**
     * File type.
     *
     * @var int
     */
    private $_type = 0;

    /**
     * File reference.
     *
     * @var string
     */
    private $_fileReference = '';
    /**
     * File URL for weblocation.
     *
     * @var string
     */
    private $_url = '';


    /**
     * File id.
     *
     * @var int
     */
    private $_id;
    /**
     * File access hash.
     *
     * @var int
     */
    private $_accessHash;


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
     * Photo size source.
     *
     * @var PhotoSizeSource
     */
    private $_photoSizeSource;
    /**
     * Basic constructor function.
     */
    public function __construct()
    {
    }

    /**
     * Decode file ID from bot API file ID.
     *
     * @param string $fileId File ID
     *
     * @return self
     */
    public static function fromBotAPI(string $fileId): self
    {
        $result = new self;
        $resultArray = internalDecode($fileId);
        $result->setVersion($resultArray['version']);
        $result->setSubVersion($resultArray['subVersion']);
        $result->setType($resultArray['typeId']);
        $result->setDcId($resultArray['dc_id']);
        $result->setAccessHash($resultArray['access_hash']);

        if ($resultArray['hasReference']) {
            $result->setFileReference($resultArray['fileReference']);
        }
        if ($resultArray['hasWebLocation']) {
            $result->setUrl($resultArray['webLocation']);
            return $result;
        }
        $result->setId($resultArray['id']);

        if ($result->getType() <= PHOTO) {
            if (isset($resultArray['volume_id'])) {
                $result->setVolumeId($resultArray['volume_id']);
            }
            if (isset($resultArray['local_id'])) {
                $result->setLocalId($resultArray['local_id']);
            }
            switch ($resultArray['photosize_source']) {
                case PHOTOSIZE_SOURCE_LEGACY:
                case PHOTOSIZE_SOURCE_FULL_LEGACY:
                    $photoSizeSource = new PhotoSizeSourceLegacy($resultArray['photosize_source']);
                    $photoSizeSource->setSecret($resultArray['secret']);
                    break;
                case PHOTOSIZE_SOURCE_THUMBNAIL:
                    $photoSizeSource = new PhotoSizeSourceThumbnail($resultArray['photosize_source']);
                    $photoSizeSource->setThumbType($resultArray['thumbnail_type']);
                    $photoSizeSource->setThumbFileType($resultArray['file_type']);
                    break;
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG_LEGACY:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL:
                    $photoSizeSource = new PhotoSizeSourceDialogPhoto($resultArray['photosize_source']);
                    $photoSizeSource->setDialogId($resultArray['dialog_id']);
                    $photoSizeSource->setDialogAccessHash($resultArray['dialog_access_hash']);
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL:
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_LEGACY:
                    $photoSizeSource = new PhotoSizeSourceStickersetThumbnail($resultArray['photosize_source']);
                    $photoSizeSource->setStickerSetId($resultArray['sticker_set_id']);
                    $photoSizeSource->setStickerSetAccessHash($resultArray['sticker_set_access_hash']);
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_VERSION:
                    $photoSizeSource = new PhotoSizeSourceStickersetThumbnailVersion($resultArray['photosize_source']);
                    $photoSizeSource->setStickerSetId($resultArray['sticker_set_id']);
                    $photoSizeSource->setStickerSetAccessHash($resultArray['sticker_set_access_hash']);
                    $photoSizeSource->setStickerSetVersion($resultArray['sticker_version']);
                    break;
            }
            $result->setPhotoSizeSource($photoSizeSource);
        }

        return $result;
    }


    /**
     * Get bot API file ID.
     *
     * @return string
     */
    public function getBotAPI(): string
    {
        $type = $this->getType();
        if ($this->hasFileReference()) {
            $type |= FILE_REFERENCE_FLAG;
        }
        if ($this->hasUrl()) {
            $type |= WEB_LOCATION_FLAG;
        }

        $fileId = \pack('VV', $type, $this->getDcId());
        if ($this->hasFileReference()) {
            $fileId .= packTLString($this->getFileReference());
        }
        if ($this->hasUrl()) {
            $fileId .= packTLString($this->getUrl());
            $fileId .= packLong($this->getAccessHash());
            return base64urlEncode(rleEncode($fileId));
        }

        $fileId .= packLong($this->getId());
        $fileId .= packLong($this->getAccessHash());

        if ($this->getType() <= PHOTO) {
            $photoSize = $this->getPhotoSizeSource();
            $fileId .= \pack('V', $photoSize->getType());
            switch ($photoSize->getType()) {
                case PHOTOSIZE_SOURCE_LEGACY:
                    $fileId .= packLong($photoSize->getSecret());
                    break;
                case PHOTOSIZE_SOURCE_FULL_LEGACY:
                    $fileId .= packLong($this->getVolumeId());
                    $fileId .= packLong($photoSize->getSecret());
                    $fileId .= \pack('l', $this->getLocalId());
                    break;
                case PHOTOSIZE_SOURCE_THUMBNAIL:
                    $fileId .= \pack('Va4', $photoSize->getThumbFileType(), $photoSize->getThumbType());
                    break;
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_BIG_LEGACY:
                case PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY:
                    $fileId .= packLongBig($photoSize->getDialogId());
                    $fileId .= packLong($photoSize->getDialogAccessHash());
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL:
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_LEGACY:
                    $fileId .= packLong($photoSize->getStickerSetId());
                    $fileId .= packLong($photoSize->getStickerSetAccessHash());
                    break;
                case PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_VERSION:
                    $fileId .= packLong($photoSize->getStickerSetId());
                    $fileId .= packLong($photoSize->getStickerSetAccessHash());
                    $fileId .= \pack('l', $photoSize->getStickerSetVersion());
                    break;
            }
            if ($photoSize->getType() >= PHOTOSIZE_SOURCE_DIALOGPHOTO_SMALL_LEGACY && $photoSize->getType() <= PHOTOSIZE_SOURCE_STICKERSET_THUMBNAIL_LEGACY) {
                $fileId .= packLong($this->getVolumeId());
                $fileId .= \pack('l', $this->getLocalId());
            }
        }

        if ($this->getVersion() >= 4) {
            $fileId .= \chr($this->getSubVersion());
        }
        $fileId .= \chr($this->getVersion());

        return base64urlEncode(rleEncode($fileId));
    }

    /**
     * Get unique file ID from file ID.
     *
     * @return UniqueFileId
     */
    public function getUnique(): UniqueFileId
    {
        return UniqueFileId::fromFileId($this);
    }
    /**
     * Get unique bot API file ID from file ID.
     *
     * @return string
     */
    public function getUniqueBotAPI(): string
    {
        return UniqueFileId::fromFileId($this)->getUniqueBotAPI();
    }
    /**
     * Get bot API file ID.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getBotAPI();
    }
    /**
     * Get bot API file ID version.
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->_version;
    }

    /**
     * Set bot API file ID version.
     *
     * @param int $_version Bot API file ID version.
     *
     * @return self
     */
    public function setVersion(int $_version): self
    {
        $this->_version = $_version;

        return $this;
    }

    /**
     * Get bot API file ID subversion.
     *
     * @return int
     */
    public function getSubVersion(): int
    {
        return $this->_subVersion;
    }

    /**
     * Set bot API file ID subversion.
     *
     * @param int $_subVersion Bot API file ID subversion.
     *
     * @return self
     */
    public function setSubVersion(int $_subVersion): self
    {
        $this->_subVersion = $_subVersion;

        return $this;
    }

    /**
     * Get file type.
     *
     * @return int
     */
    public function getType(): int
    {
        return $this->_type;
    }

    /**
     * Get file type as string.
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return TYPES[$this->_type];
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
     * Get file reference.
     *
     * @return string
     */
    public function getFileReference(): string
    {
        return $this->_fileReference;
    }

    /**
     * Set file reference.
     *
     * @param string $_fileReference File reference.
     *
     * @return self
     */
    public function setFileReference(string $_fileReference): self
    {
        $this->_fileReference = $_fileReference;

        return $this;
    }

    /**
     * Check if has file reference.
     *
     * @return boolean
     */
    public function hasFileReference(): bool
    {
        return !empty($this->_fileReference);
    }

    /**
     * Get file URL for weblocation.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * Check if has file URL.
     *
     * @return boolean
     */
    public function hasUrl(): bool
    {
        return !empty($this->_url);
    }

    /**
     * Set file URL for weblocation.
     *
     * @param string $_url File URL for weblocation.
     *
     * @return self
     */
    public function setUrl(string $_url): self
    {
        $this->_url = $_url;

        return $this;
    }

    /**
     * Get file id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Set file id.
     *
     * @param int $_id File id.
     *
     * @return self
     */
    public function setId($_id): self
    {
        $this->_id = $_id;

        return $this;
    }

    /**
     * Check if has file id.
     *
     * @return bool
     */
    public function hasId(): bool
    {
        return isset($this->_id);
    }

    /**
     * Get file access hash.
     *
     * @return int
     */
    public function getAccessHash()
    {
        return $this->_accessHash;
    }

    /**
     * Set file access hash.
     *
     * @param int $_accessHash File access hash.
     *
     * @return self
     */
    public function setAccessHash($_accessHash): self
    {
        $this->_accessHash = $_accessHash;

        return $this;
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
     * Get photo size source.
     *
     * @return PhotoSizeSource
     */
    public function getPhotoSizeSource(): PhotoSizeSource
    {
        return $this->_photoSizeSource;
    }

    /**
     * Set photo size source.
     *
     * @param PhotoSizeSource $_photoSizeSource Photo size source.
     *
     * @return self
     */
    public function setPhotoSizeSource(PhotoSizeSource $_photoSizeSource): self
    {
        $this->_photoSizeSource = $_photoSizeSource;

        return $this;
    }

    /**
     * Check if has photo size source.
     *
     * @return boolean
     */
    public function hasPhotoSizeSource(): bool
    {
        return isset($this->_photoSizeSource);
    }

    /**
     * Get dC ID.
     *
     * @return int
     */
    public function getDcId(): int
    {
        return $this->_dcId;
    }

    /**
     * Set dC ID.
     *
     * @param int $_dcId DC ID.
     *
     * @return self
     */
    public function setDcId(int $_dcId): self
    {
        $this->_dcId = $_dcId;

        return $this;
    }
}

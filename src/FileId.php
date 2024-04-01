<?php declare(strict_types=1);
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
     */
    private int $version = 4;

    /**
     * Bot API file ID subversion.
     *
     */
    private int $subVersion = 47;

    /**
     * DC ID.
     *
     */
    private int $dcId = 0;

    /**
     * File type.
     *
     */
    private int $type = 0;

    /**
     * File reference.
     *
     */
    private string $fileReference = '';
    /**
     * File URL for weblocation.
     *
     */
    private string $url = '';

    /**
     * File id.
     *
     */
    private int $id;
    /**
     * File access hash.
     *
     */
    private int $accessHash;

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
     * Photo size source.
     *
     */
    private PhotoSizeSource $photoSizeSource;
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
            $result->setUrl($resultArray['url']);
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
     */
    public function getUnique(): UniqueFileId
    {
        return UniqueFileId::fromFileId($this);
    }
    /**
     * Get unique bot API file ID from file ID.
     *
     */
    public function getUniqueBotAPI(): string
    {
        return UniqueFileId::fromFileId($this)->getUniqueBotAPI();
    }
    /**
     * Get bot API file ID.
     *
     */
    public function __toString(): string
    {
        return $this->getBotAPI();
    }
    /**
     * Get bot API file ID version.
     *
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Set bot API file ID version.
     *
     * @param int $version Bot API file ID version.
     *
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get bot API file ID subversion.
     *
     */
    public function getSubVersion(): int
    {
        return $this->subVersion;
    }

    /**
     * Set bot API file ID subversion.
     *
     * @param int $subVersion Bot API file ID subversion.
     *
     */
    public function setSubVersion(int $subVersion): self
    {
        $this->subVersion = $subVersion;

        return $this;
    }

    /**
     * Get file type.
     *
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Get file type as string.
     *
     */
    public function getTypeName(): string
    {
        return TYPES[$this->type];
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
     * Get file reference.
     *
     */
    public function getFileReference(): string
    {
        return $this->fileReference;
    }

    /**
     * Set file reference.
     *
     * @param string $fileReference File reference.
     *
     */
    public function setFileReference(string $fileReference): self
    {
        $this->fileReference = $fileReference;

        return $this;
    }

    /**
     * Check if has file reference.
     *
     * @return boolean
     */
    public function hasFileReference(): bool
    {
        return !empty($this->fileReference);
    }

    /**
     * Get file URL for weblocation.
     *
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Check if has file URL.
     *
     * @return boolean
     */
    public function hasUrl(): bool
    {
        return !empty($this->url);
    }

    /**
     * Set file URL for weblocation.
     *
     * @param string $url File URL for weblocation.
     *
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get file id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set file id.
     *
     * @param int $id File id.
     *
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Check if has file id.
     *
     */
    public function hasId(): bool
    {
        return isset($this->id);
    }

    /**
     * Get file access hash.
     *
     * @return int
     */
    public function getAccessHash()
    {
        return $this->accessHash;
    }

    /**
     * Set file access hash.
     *
     * @param int $accessHash File access hash.
     *
     */
    public function setAccessHash(int $accessHash): self
    {
        $this->accessHash = $accessHash;

        return $this;
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
     * Get photo size source.
     *
     */
    public function getPhotoSizeSource(): PhotoSizeSource
    {
        return $this->photoSizeSource;
    }

    /**
     * Set photo size source.
     *
     * @param PhotoSizeSource $photoSizeSource Photo size source.
     *
     */
    public function setPhotoSizeSource(PhotoSizeSource $photoSizeSource): self
    {
        $this->photoSizeSource = $photoSizeSource;

        return $this;
    }

    /**
     * Check if has photo size source.
     *
     * @return boolean
     */
    public function hasPhotoSizeSource(): bool
    {
        return isset($this->photoSizeSource);
    }

    /**
     * Get dC ID.
     *
     */
    public function getDcId(): int
    {
        return $this->dcId;
    }

    /**
     * Set dC ID.
     *
     * @param int $dcId DC ID.
     *
     */
    public function setDcId(int $dcId): self
    {
        $this->dcId = $dcId;

        return $this;
    }
}

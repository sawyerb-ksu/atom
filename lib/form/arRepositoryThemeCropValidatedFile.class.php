<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This class extends the save method in sfValidatedFile so the logo or banner
 * uploaded in repository/editThemeAction.class.php can be cropped to fit the
 * application requirements.
 */
class arRepositoryThemeCropValidatedFile extends sfValidatedFile
{
    // Max dimensions in pixels
    public const LOGO_MAX_WIDTH = 270;
    public const LOGO_MAX_HEIGHT = 270;
    public const BANNER_MAX_WIDTH = 800;
    public const BANNER_MAX_HEIGHT = 300;
    public const IMAGICK_MEMORY_LIMIT_MB = 64;
    public const IMAGICK_MAP_LIMIT_MB = 64;
    public const IMAGICK_AREA_LIMIT_PIXELS = 16000000;

    public function save($file = null, $fileMode = 0666, $create = true, $dirMode = 0777)
    {
        $file = parent::save($file, $fileMode, $create, $dirMode);

        if (!$this->shouldCropImages()) {
            return $file;
        }

        if (null === $dimensions = self::getTargetDimensionsFromPath($this->savedName)) {
            return $file;
        }

        $this->resizeAndCropImage($dimensions['width'], $dimensions['height']);

        return $file;
    }

    public static function getTargetDimensionsFromPath($path)
    {
        $pathInfo = pathinfo($path);

        switch ($pathInfo['filename']) {
            case 'logo':
                return [
                    'width' => self::LOGO_MAX_WIDTH,
                    'height' => self::LOGO_MAX_HEIGHT,
                ];

            case 'banner':
                return [
                    'width' => self::BANNER_MAX_WIDTH,
                    'height' => self::BANNER_MAX_HEIGHT,
                ];
        }
    }

    public static function getResizeGeometry($sourceWidth, $sourceHeight, $targetWidth, $targetHeight)
    {
        if (
            0 >= $sourceWidth
            || 0 >= $sourceHeight
            || 0 >= $targetWidth
            || 0 >= $targetHeight
        ) {
            return null;
        }

        // Scale to fully cover the target box while preserving aspect ratio.
        $scale = max($targetWidth / $sourceWidth, $targetHeight / $sourceHeight);
        $resizeWidth = (int) ceil($sourceWidth * $scale);
        $resizeHeight = (int) ceil($sourceHeight * $scale);

        return [
            // Crop back to the exact target size from the centered overflow.
            'resizeWidth' => $resizeWidth,
            'resizeHeight' => $resizeHeight,
            'cropX' => max(0, (int) floor(($resizeWidth - $targetWidth) / 2)),
            'cropY' => max(0, (int) floor(($resizeHeight - $targetHeight) / 2)),
        ];
    }

    protected function shouldCropImages()
    {
        return QubitDigitalObject::imagickExtensionLoaded();
    }

    protected function resizeAndCropImage($width, $height)
    {
        try {
            $image = new Imagick();
            $image->setResourceLimit(Imagick::RESOURCETYPE_MEMORY, self::IMAGICK_MEMORY_LIMIT_MB);
            $image->setResourceLimit(Imagick::RESOURCETYPE_MAP, self::IMAGICK_MAP_LIMIT_MB);
            $image->setResourceLimit(Imagick::RESOURCETYPE_AREA, self::IMAGICK_AREA_LIMIT_PIXELS);
            $image->readImage($this->savedName);
            $geometry = self::getResizeGeometry(
                $image->getImageWidth(),
                $image->getImageHeight(),
                $width,
                $height
            );

            if (null === $geometry) {
                $image->clear();
                $image->destroy();

                return;
            }

            $image->resizeImage(
                $geometry['resizeWidth'],
                $geometry['resizeHeight'],
                Imagick::FILTER_LANCZOS,
                1
            );
            $image->cropImage($width, $height, $geometry['cropX'], $geometry['cropY']);
            $image->setImagePage(0, 0, 0, 0);
            $image->stripImage();
            $image->setImageCompressionQuality(90);
            $image->writeImage($this->savedName);
            $image->clear();
            $image->destroy();
        } catch (Exception $e) {
            // Leave the uploaded file untouched if Imagick cannot process it.
        }
    }
}

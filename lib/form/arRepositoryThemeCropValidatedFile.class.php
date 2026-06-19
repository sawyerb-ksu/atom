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

    public function save($file = null, $fileMode = 0666, $create = true, $dirMode = 0777)
    {
        $file = parent::save($file, $fileMode, $create, $dirMode);

        if (!$this->shouldCropImages()) {
            return $file;
        }

        if (null === $dimensions = self::getCropDimensionsFromPath($this->savedName)) {
            return $file;
        }

        $this->cropImage($dimensions['width'], $dimensions['height']);

        return $file;
    }

    public static function getCropDimensionsFromPath($path)
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

    protected function shouldCropImages()
    {
        return QubitDigitalObject::imagickExtensionLoaded();
    }

    protected function cropImage($width, $height)
    {
        try {
            $image = new Imagick($this->savedName);

            $cropWidth = min($width, $image->getImageWidth());
            $cropHeight = min($height, $image->getImageHeight());

            $image->cropImage($cropWidth, $cropHeight, 0, 0);
            $image->setImagePage(0, 0, 0, 0);
            $image->writeImage($this->savedName);
            $image->clear();
            $image->destroy();
        } catch (Exception $e) {
            // Leave the uploaded file untouched if Imagick cannot process it.
        }
    }
}

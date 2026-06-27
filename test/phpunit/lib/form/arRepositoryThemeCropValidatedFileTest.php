<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../../../lib/form/arRepositoryThemeCropValidatedFile.class.php';

/**
 * @internal
 *
 * @covers \arRepositoryThemeCropValidatedFile
 */
class arRepositoryThemeCropValidatedFileTest extends TestCase
{
    public function testLogoUploadsUseExpectedTargetDimensions()
    {
        $this->assertSame(
            [
                'width' => arRepositoryThemeCropValidatedFile::LOGO_MAX_WIDTH,
                'height' => arRepositoryThemeCropValidatedFile::LOGO_MAX_HEIGHT,
            ],
            arRepositoryThemeCropValidatedFile::getTargetDimensionsFromPath('/tmp/logo.png')
        );
    }

    public function testBannerUploadsUseExpectedTargetDimensions()
    {
        $this->assertSame(
            [
                'width' => arRepositoryThemeCropValidatedFile::BANNER_MAX_WIDTH,
                'height' => arRepositoryThemeCropValidatedFile::BANNER_MAX_HEIGHT,
            ],
            arRepositoryThemeCropValidatedFile::getTargetDimensionsFromPath('/tmp/banner.png')
        );
    }

    public function testUnknownUploadNamesAreNotCropped()
    {
        $this->assertNull(arRepositoryThemeCropValidatedFile::getTargetDimensionsFromPath('/tmp/other.png'));
    }

    public function testResizeGeometryForLandscapeLogoCentersHorizontalCrop()
    {
        $this->assertSame(
            [
                'resizeWidth' => 540,
                'resizeHeight' => 270,
                'cropX' => 135,
                'cropY' => 0,
            ],
            arRepositoryThemeCropValidatedFile::getResizeGeometry(1200, 600, 270, 270)
        );
    }

    public function testResizeGeometryForPortraitLogoCentersVerticalCrop()
    {
        $this->assertSame(
            [
                'resizeWidth' => 270,
                'resizeHeight' => 540,
                'cropX' => 0,
                'cropY' => 135,
            ],
            arRepositoryThemeCropValidatedFile::getResizeGeometry(600, 1200, 270, 270)
        );
    }

    public function testResizeGeometryForBannerCentersVerticalCrop()
    {
        $this->assertSame(
            [
                'resizeWidth' => 800,
                'resizeHeight' => 600,
                'cropX' => 0,
                'cropY' => 150,
            ],
            arRepositoryThemeCropValidatedFile::getResizeGeometry(1600, 1200, 800, 300)
        );
    }

    public function testResizeGeometryRejectsInvalidDimensions()
    {
        $this->assertNull(arRepositoryThemeCropValidatedFile::getResizeGeometry(0, 1200, 270, 270));
    }

    public function testCroppingRequiresImagickExtension()
    {
        $file = new TestRepositoryThemeCropValidatedFile('logo.png', 'image/png', '/tmp/logo.png', 0);

        $this->assertSame(extension_loaded('imagick'), $file->shouldCropImagesForTest());
    }
}

class TestRepositoryThemeCropValidatedFile extends arRepositoryThemeCropValidatedFile
{
    public function shouldCropImagesForTest()
    {
        return $this->shouldCropImages();
    }
}

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
    public function testLogoUploadsUseExpectedCropDimensions()
    {
        $this->assertSame(
            [
                'width' => arRepositoryThemeCropValidatedFile::LOGO_MAX_WIDTH,
                'height' => arRepositoryThemeCropValidatedFile::LOGO_MAX_HEIGHT,
            ],
            arRepositoryThemeCropValidatedFile::getCropDimensionsFromPath('/tmp/logo.png')
        );
    }

    public function testBannerUploadsUseExpectedCropDimensions()
    {
        $this->assertSame(
            [
                'width' => arRepositoryThemeCropValidatedFile::BANNER_MAX_WIDTH,
                'height' => arRepositoryThemeCropValidatedFile::BANNER_MAX_HEIGHT,
            ],
            arRepositoryThemeCropValidatedFile::getCropDimensionsFromPath('/tmp/banner.png')
        );
    }

    public function testUnknownUploadNamesAreNotCropped()
    {
        $this->assertNull(arRepositoryThemeCropValidatedFile::getCropDimensionsFromPath('/tmp/other.png'));
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

<?php

namespace Peat;
class Image extends BaseElement
{
    protected array $sizes;

    public function __construct(\stdClass $properties = null)
    {
        parent::__construct($properties);
        $this->type_name = 'image';
        $this->sizes = array(
            'huge' => 4000,
            'large' => 1600,
            'medium' => 800,
            'small' => 400,
            'tiny' => 200,
        ); // TODO in config or something?
    }

    public function create(): ?int
    {
        return $this->getDB()->insertElement($this->getType(), array(
            'title' => __('New image', 'peatcms'),
            'content_type' => 'image/jpg',
            'filename_saved' => '',
            'filename_original' => '',
            'extension' => 'tbd',
            'slug' => 'image',
        ));
    }

    // image resize: https://stackoverflow.com/questions/14649645/resize-image-in-php#answer-56039606
    public function process(LoggerInterface $logger): bool
    {
        // the saved file should be split up in different sizes (according to instance?) and saved (compressed) under the slug name.
        // file name contains slug and instance_id and size denominator
        // TODO if the slug changes, the saved files need to change as well, check in history periodically
        // TODO and make it async, for instance with cron jobs
        $data = array(); // the columns to update
        $path = Setup::$UPLOADS . $this->row->filename_saved;
        // check physical (image) file
        if (! file_exists($path)) return false;
        if (false === in_array(($type = exif_imagetype($path)),
                [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP]
            )) return false;
        list($width, $height) = getimagesize($path);
        switch (true) {
            case $type === IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                $data['extension'] = 'jpg';
                break;
            case $type === IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                $data['extension'] = 'png';
                break;
            case $type === IMAGETYPE_GIF:
                $image = imagecreatefromgif($path);
                $data['extension'] = 'gif';
                break;
            case $type === IMAGETYPE_BMP:
                $image = imagecreatefrombmp($path);
                $data['extension'] = 'bmp';
                break;
            case $type === IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($path);
                $data['extension'] = 'webp';
        }
        if (false === isset($image)) return false; // to satisfy phpstorm regarding '$image might not be defined'
        $logger->log(sprintf('Loaded %s image in memory', $data['extension']));
        // define necessary paths
        $src = $this->getSlug() . '.%s.webp'; // we save as webp by default with jpg fallback
        define('PATH', CORE . '../htdocs/img/');
        // process and save the 5 sizes TODO compact this somewhere <- don't forget to include the check on existence
        foreach ($this->sizes as $size => $pixels) { // (e.g. 'small' => 400)
            set_time_limit(30);
            if ($width > $height) { // landscape
                if ($width < $pixels) {
                    $newWidth = $width;
                    $newHeight = $height;
                } else {
                    $newWidth = $pixels;
                    $newHeight = floor($pixels * $height / $width);
                }
            } else {
                if ($height < $pixels) {
                    $newHeight = $height;
                    $newWidth = $width;
                } else {
                    $newHeight = $pixels;
                    $newWidth = floor($pixels * $width / $height);
                }
            }
            // create resized image
            $logger->log('Preparing new image');
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            //TRANSPARENT BACKGROUND
            $color = imagecolorallocatealpha($newImage, 0, 0, 0, 127); //fill transparent back
            imagefill($newImage, 0, 0, $color);
            imagesavealpha($newImage, true);
            //ROUTINE
            $logger->log(sprintf('Resizing original to %s × %s', $newWidth, $newHeight));
            imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            // save the img
            $newPath = sprintf($src, $size); // webp path
            // never overwrite images
            $index = 1;
            while (file_exists(PATH . $newPath)) {
                $index++;
                $newPath = sprintf($src, $size . $index);
            }
            $logger->log(sprintf('Saving to %s', $newPath));
            // save as jpg and as webp only @since 0.5.9
            $success = imagewebp($newImage, PATH . $newPath, 55);
            if (true === $success) { // now save as jpg for fallback, without any alpha stuff
                $logger->log('Saved');
                $newImage = $this->removeAlpha($newImage, 255, 255, 255);
                $jpgPath = substr($newPath, 0, -4) . 'jpg';
                // in webserver config a rule redirects webp requests to jpg if the webp accept header is missing
                $success = imagejpeg($newImage, PATH . $jpgPath, 55);
            }
            imagedestroy($newImage);
            // remember values
            if (true === $success) {
                $logger->log('Saved fallback jpg image');
                $data['src_' . $size] = $newPath; // always requests webp, will be redirected to jpg by /img/.htaccess
                $data['width_' . $size] = $newWidth;
                $data['height_' . $size] = $newHeight;
            } else {
                $this->addError(sprintf(__('Could not save image %s', 'peatcms'), $newPath));

                return false;
            }
        }
        imagedestroy($image);
        // update the element
        if (true === $this->update($data)) {
            $logger->log('Saved info to database');

            return true;
        }

        return false;
    }

    private function removeAlpha($img, int $red, int $green, int $blue)
    {
        // check the values of RGB: must be a positive int smaller than 256
        $red = min(abs($red), 255);
        $green = min(abs($green), 255);
        $blue = min(abs($blue), 255);
        // get image width and height
        $w = imagesx($img);
        $h = imagesy($img);
        // turn alpha blending off
        imagealphablending($img, false);
        // set the color
        $color = imagecolorallocate($img, $red, $green, $blue);
        // loop through the image and replace any alpha'd pixel by the supplied background color
        // TODO mix the colors so it doesn't get jagged
        for ($x = 0; $x < $w; $x++)
            for ($y = 0; $y < $h; $y++) {
                if (0 !== (imagecolorat($img, $x, $y) >> 24) & 0xFF) {
                    //set pixel with the new color
                    if (! imagesetpixel($img, $x, $y, $color)) {
                        return null;
                    }
                }
            }

        return $img;
    }

    public function completeRowForOutput(): void // override from base element class
    {
        // TODO make this normal, not adding '/img/' here
        $this->row->src_tiny = '/img/' . $this->row->src_tiny;
        $this->row->src_small = '/img/' . $this->row->src_small;
        $this->row->src_medium = '/img/' . $this->row->src_medium;
        $this->row->src_large = '/img/' . $this->row->src_large;
        $this->row->src_huge = '/img/' . $this->row->src_huge;
    }
}

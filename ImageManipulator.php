<?php
class ImageManipulator {
    private $image;
    private $width;
    private $height;
    private $filetype;
    static $LIGHTNESS = 0;
    static $AVERAGE = 1;
    static $LUMINOSITY = 2;
    
    public function __construct($filename) {
        set_time_limit(0);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $this->filetype = finfo_file($finfo,$filename);
        
        if($this->filetype == 'image/png')
            $this->image = imagecreatefrompng($filename);
        elseif($this->filetype == 'image/jpeg')
            $this->image = imagecreatefromjpeg($filename);
        elseif($this->filetype == 'image/gif')
            $this->image = imagecreatefromgif($filename);
        elseif($this->filetype == 'image/bmp')
            $this->image = imagecreatefromwbmp($filename);
        else
            die('Unsupported format');
        
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }
    
    public function grayscale($algorithm){
        switch ($algorithm){
            case self::$AVERAGE:
                $this->grayscale_average();
                break;
            case self::$LIGHTNESS:
                $this->grayscale_lightness();
                break;
            case self::$LUMINOSITY:
                $this->grayscale_luminosity();
                break;
        }
    }
    
    private function pixel_convolution($matrix,$x,$y, $div = 1, $offset = 0) {
        $div = ($div == 0? 1: $div);
        $convultion_mask = array(
            'red'=>0,
            'green'=>0,
            'blue'=>0
        );
        
        $n = (sizeof($matrix) - 1) / 2;
        
        for ($i = 0; $i < sizeof($matrix); $i++) {
            $py = (($py = $y - $n + $i) < 0 ? 0 : ($py > $this->height - 1 ? $this->height - 1 : $py));
            for ($j = 0; $j < sizeof($matrix); $j++) {
                $px = (($px = $x - $n + $j) < 0 ? 0 : ($px > $this->width - 1 ? $this->width - 1 : $px));
                foreach (imagecolorsforindex($this->image, (imagecolorat($this->image, $px, $py))) as $color => $intensity) {
                    $convultion_mask[$color] += $intensity * $matrix[$j][$i];
                }
            }
        }
        
        foreach($convultion_mask as $color => $intensity){
            $intensity = $convultion_mask[$color] = $intensity / $div + $offset;
            if ($intensity > 255)
                $convultion_mask[$color] = 255;
            if ($intensity < 0)
                $convultion_mask[$color] = 0;
        }
        
        return $convultion_mask;
    }
    
    public function gaussian_filter(){
        /*$gaussian_blur = array(
            array(1, 2, 1),
            array(2, 4, 2),
            array(1, 2, 1)
        );*/
        $gaussian_blur = array(
            array(0,  0,  1,  2,  1,  0,  0),
            array(0,  1,  2,  4,  2,  1,  0),
            array(1,  2,  4,  8,  4,  2,  1),
            array(2,  4,  8,  16, 8,  4,  2),
            array(1,  2,  4,  8,  4,  2,  1),
            array(0,  1,  2,  4,  2,  1,  0),
            array(0,  0,  1,  2,  1,  0,  0),
        );
        $div = array_sum(array_map('array_sum', $gaussian_blur));
        
        $buffer = imagecreatetruecolor($this->width, $this->height);
        for($y = 0; $y < $this->height; $y++)
            for($x = 0; $x < $this->width; $x++){
                $mask = $this->pixel_convolution($gaussian_blur, $x,$y, $div);
                $new_color = imagecolorallocate($buffer, $mask['red'],$mask['green'], $mask['blue']);
                imagesetpixel($buffer, $x,$y, $new_color);
            }
            
        $this->setImage($buffer);
    }
    
    public function sobel_detect_edges(){
        $sobel_matrix_x = array(
                    array(-1, 0, 1),
                    array(-2, 0, 2),
                    array(-1, 0, 1)
                );
        $sobel_matrix_y = array(
                    array(-1,-2,-1),
                    array(0,  0, 0),
                    array(1,  2, 1)
                );
        
        $buffer = imagecreatetruecolor($this->width, $this->height);
        for ($y = 0; $y < $this->height; $y++)
            for ($x = 0; $x < $this->width; $x++) {
                $convultion_mask_x = $this->pixel_convolution($sobel_matrix_x, $x, $y,1,0);
                $convultion_mask_y = $this->pixel_convolution($sobel_matrix_y, $x, $y,1,0);
                
                $convultion_mask = array();
                foreach($convultion_mask_x as $color => $value) {
                    $convultion_mask[$color] = sqrt(pow($value, 2)+pow($convultion_mask_y[$color],2));
                    
                    if($convultion_mask[$color] > 255) $convultion_mask[$color] = 255;
                    elseif($convultion_mask[$color] < 0) $convultion_mask[$color] = 0;
                }
                $new_color = imagecolorallocate($buffer, $convultion_mask['red'], $convultion_mask['green'], $convultion_mask['blue']);
                
                imagesetpixel($buffer, $x, $y, $new_color);
            }
            $this->setImage($buffer);
    }
    
    public function edge_test($threshold) {
        $buffer = imagecreatetruecolor($this->width, $this->height);
        for ($y = 0; $y < $this->height; $y++){
            for ($x = 0; $x < $this->width; $x++) {
                
                $gray = array_sum(imagecolorsforindex($this->image, (imagecolorat($this->image, $px, $py))))/3;
                
                $maximum = false;
                for ($i = 0; $i < 3; $i++) {
                    $py = (($py = $y - 1 + $i) < 0 ? 0 : ($py > $this->height - 1 ? $this->height - 1 : $py));
                    for ($j = 0; $j < 3; $j++) {
                        $px = (($px = $x - 1 + $j) < 0 ? 0 : ($px > $this->width - 1 ? $this->width - 1 : $px));
                        $intensity = array_sum(imagecolorsforindex($this->image, (imagecolorat($this->image, $px, $py))))/3;
                        if(($intensity - $gray) >= $threshold){
                            $px_shift = (($px = $x - 2 + $j) < 0 ? 0 : ($px > $this->width - 1 ? $this->width - 1 : $px));
                            $py_shift = (($py = $y - 2 + $i) < 0 ? 0 : ($py > $this->height - 1 ? $this->height - 1 : $py));
                            $intensity = array_sum(imagecolorsforindex($this->image, (imagecolorat($this->image, $px_shift, $py_shift))))/3;
                            if(($intensity - $gray) >= $threshold) {
                                $maximum = true;
                                break;
                            }
                        }
                        
                        if($maximum) break;
                    }
                }
                if($maximum)
                    imagesetpixel($buffer, $x, $y, imagecolorallocate($buffer, 255, 255, 255));
                else
                    imagesetpixel($buffer, $x, $y, imagecolorallocate($buffer, 0,0,0));
            }
        }
        $this->setImage($buffer);
    }

    private function grayscale_average() {
        for ($y = 0; $y < $this->height; $y++)
            for ($x = 0; $x < $this->width; $x++) {
                $rgb = imagecolorat($this->image, $x, $y);
                $color = imagecolorsforindex($this->image, $rgb);
                $gray = ($color['red'] + $color['green'] + $color['green']) / 3;

                $new_color = imagecolorallocate($this->image, $gray, $gray, $gray);
                imagesetpixel($this->image, $x, $y, $new_color);
            }
    }
    
    private function grayscale_lightness() {
        for ($y = 0; $y < $this->height; $y++)
            for ($x = 0; $x < $this->width; $x++) {
                $rgb = imagecolorat($this->image, $x, $y);
                $color = imagecolorsforindex($this->image, $rgb);
                $gray = (min(array($color['red'],$color['green'],$color['blue']))+max(array($color['red'],$color['green'],$color['blue']))) / 2;

                $new_color = imagecolorallocate($this->image, $gray, $gray, $gray);
                imagesetpixel($this->image, $x, $y, $new_color);
            }
    }
    
    private function grayscale_luminosity(){
        for ($y = 0; $y < $this->height; $y++)
            for ($x = 0; $x < $this->width; $x++) {
                $rgb = imagecolorat($this->image, $x, $y);
                $color = imagecolorsforindex($this->image, $rgb);
                $gray = ($color['red']*0.299 + $color['green']*0.587 + $color['blue']*0.114);
                
                $new_color = imagecolorallocate($this->image, $gray, $gray, $gray);
                imagesetpixel($this->image, $x, $y, $new_color);
            }
    }
    
    public function showImage(){
        header('Content-type: '.$this->filetype);
        if($this->filetype == 'image/png')
            imagepng($this->image);
        elseif($this->filetype == 'image/jpeg')
            imagejpeg($this->image);
        elseif($this->filetype == 'image/gif')
            imagegif($this->image);
        elseif($this->filetype == 'image/bmp')
            imagewbmp($this->image);
        else
            die('Unsupported format');
        exit(0);
    }
    
    public function close(){
        imagedestroy($this->image);
    }
    
    public function setImage($image){
        $this->image = $image;
    }
    
}

?>

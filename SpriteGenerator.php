<?php
/**
 * Copyright (c) 2008, Chris Morrell
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * 
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 * 
 *  - Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 
 *  - Neither the name of Chris Morrell nor the names of its
 *    contributors may be used to endorse or promote products derived from this
 *    software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. 
 * 
 * @category  Utilities
 * @package   SpriteGenerator
 * @copyright Copyright (c) 2008 Chris Morrell
 * @author    Saul Rosenbaum / visualchutzpah.com
 * @author    Chris Morrell / cmorrell.com
 * @license   New BSD (see above)
 */

/**
 * CSS Sprite Generator Class
 * 
 * Generates CSS sprite pairs either based on an array of pairs or from a
 * directory based on filename rules.  Two examples:
 * 
 * <code>
 * <?php
 * require 'SpriteGenerator.php';
 * $sg = new SpriteGenerator(array(array('file1.gif'), array('file1b.gif'));
 * $sg->batchSprites();
 * ?>
 * </code>
 * 
 * The above code will overwrite file1.gif with a image containing file1.gif
 * and file1b.gif vertically stacked.
 * 
 * <code>
 * <?php
 * require 'SpriteGenerator.php';
 * $sg = new SpriteGenerator('./images/');
 * $sg->batchSprites();
 * ?>
 * </code>
 * 
 * The above code will parse ./images/ and create sprites on using file.ext and
 * file_over.ext (for example file1.gif and file1_over.gif will be converted to
 * file1.gif).
 * 
 * If you would like to use a different matching method, pass a regular expression
 * as the $match1 and $match2 variables.  The first substring match is the filename
 * base (in the above example "file1") and the second substring match is the extension
 * (in the above example "gif").  If you'd rather use something like "myfile-a.gif" and
 * "myfile-b.gif" you could use the following two regular expressions:
 * 
 *  - Match 1: /^([a-z0-9]+)-a\.(jpg|jpeg|jpe|png|gif)$/i
 *  - Match 2: /^([a-z0-9]+)-b\.(jpg|jpeg|jpe|png|gif)$/i
 * 
 * @category  Utilities
 * @package   SpriteGenerator
 * @copyright Copyright (c) 2008 Chris Morrell
 * @author    Saul Rosenbaum / visualchutzpah.com
 * @author    Chris Morrell / cmorrell.com
 * @license   New BSD (see above)
 */
class SpriteGenerator
{
    /**
     * Internal variable to hold each pair of images to be
     * turned into a sprite
     *
     * @var array
     */
    var $_pairs = array();
    
    /**
     * Set this to the format you would like to output (gif, png, jpeg)
     *
     * @var string
     */
    var $defaultOutput = 'png';
    
    /**
     * Contructor
     * 
     * Pass the constructor an array and it'll use that array as its
     * pair images.  Pass it a directory and it'll parse that directory
     * and create pairs based on the images found there.
     *
     * @param array|string $input
     * @return SpriteGenerator
     */
    function SpriteGenerator($input = null, $match1 = '/^([a-z0-9]+)\.(jpg|jpeg|jpe|png|gif)$/i', $match2 = '/^([a-z0-9]+)_over\.(jpg|jpeg|jpe|png|gif)$/i')
    {
        if (!is_null($input))
        {
            if (is_array($input)) $this->_pairs = $input;
            elseif (is_dir($input)) $this->_parseDirectory($input, $match1, $match2);
            else die('Unable to auto-detect ' . var_export($input, true));
        }
    }
    
    /**
     * Setter function for _pairs
     *
     * @param array $pairs
     * @return boolean
     */
    function setPairs($pairs = array())
    {
        if (!is_array($pairs)) return false;
        
        $this->_pairs = $pairs;
        return true;
    }
    
    /**
     * Sets the directory to generate pairs from
     *
     * @param string $directory
     * @return boolean
     */
    function setDirectory($directory, $match1 = '/^([a-z0-9]+)\.(jpg|jpeg|jpe|png|gif)$/i', $match2 = '/^([a-z0-9]+)_over\.(jpg|jpeg|jpe|png|gif)$/i')
    {
        if (is_dir($directory)) return $this->_parseDirectory($input, $match1, $match2);
        return false;
    }
    
    /**
     * Parses a passed directory
     *
     * @param string $directory
     */
    function _parseDirectory($directory, $match1, $match2)
    {
        if (!$handle = opendir($directory)) return false;
        
        $pairs = array();
        while (false !== ($file = readdir($handle)))
        {
            if ($file == "." || $file == "..") continue;
            
            if (preg_match($match1, $file, $matches)) $pairs[$matches[1]][1] = $directory . $file; // TODO: Check for separator
            elseif (preg_match($match2, $file, $matches)) $pairs[$matches[1]][2] = $directory . $file;
        }
        closedir($handle);
        
        foreach ($pairs as $pair)
            $this->_pairs[] = array($pair[1], $pair[2]);
    }
    
    /**
     * Batch creates sprites for all image pairs
     *
     */
    function batchSprites()
    {
        foreach ($this->_pairs as $pair)
        {
            $image = $this->generateSprite($pair[0], $pair[1]);
            $this->_writeSprite($image, $pair[0]);
			unlink($pair[1]);
        }
    }
    
    /**
     * Generates a sprite image based on two files
     *
     * @param string $file1
     * @param string $file2
     * @return resource
     */
    function generateSprite($file1, $file2)
    {        
        $image1 = $this->_createImageFromFile($file1) or die("Cannot open {$file1}.");
        $image2 = $this->_createImageFromFile($file2) or die("Cannot open {$file2}.");
        
        $imageWidth1 = imagesx($image1);
        $imageWidth2 = imagesx($image2);

        $imageHeight1 = imagesy($image1);
        $imageHeight2 = imagesy($image2);
        
        $width = ($imageWidth1 > $imageWidth2 ? $imageWidth1 : $imageWidth2);
        $height = $imageHeight1 + $imageHeight2;
        
        $image = @imagecreatetruecolor($width, $height) or die('Unable to create sprite.');
        imagecopymerge($image, $image1, 0, 0, 0, 0, $imageWidth1, $imageHeight1, 100) or die('Unable to create sprite.');
        imagecopymerge($image, $image2, 0, $imageHeight1, 0, 0, $imageWidth2, $imageHeight2, 100) or die('Unable to create sprite.');
        
        imagedestroy($image1);
        imagedestroy($image2);
        
        return $image;
    }
    
    /**
     * Internal method that creates an image resource from a file name (choosing
     * the correct GD function based on file extension)
     *
     * @param string $filename
     * @return resource
     */
    function _createImageFromFile($filename)
    {
        if (!is_readable($filename)) die("Unable to read {$filename}");
        
        preg_match("|\.([a-z0-9]{2,4})$|i", $filename, $matches);
        $extension = $matches[1];
        switch ($extension)
        {
            case 'jpg':
            case 'jpeg':
            case 'jpe':
                return @imagecreatefromjpeg($filename);
                
            case 'png':
                return @imagecreatefrompng($filename);
                
            case 'gif':
                return @imagecreatefromgif($filename);
            
            default:
                die("Unable to recognize {$filename}'s image type.");
        }
        
        return false;
    }
    
    /**
     * Internal method for writing a sprite to disk
     *
     * @param resource $image
     * @param string $filename
     */
    function _writeSprite($image, $filename)
    {
        $function = 'image' . $this->defaultOutput;
        
        if (file_exists($filename) && !is_writable($filename)) die("{$filename} is not writable!");
        $function($image, $filename) or die ("Cannot write {$filename}");
        imagedestroy($image);
    }
}

/*

// Example Usage:
$sg = new SpriteGenerator("./images/");
$sg->batchSprites();

*/
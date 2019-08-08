<?php namespace DeftCMS\Libraries;

use DeftCMS\Engine;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Библиотека для загрузки файлов и работы с изоброжениями
 *
 * @package     DeftCMS
 * @author	    b1tc0re
 * @copyright   2017-2019 DeftCMS (https://deftcms.org/)
 * @since	    Version 0.0.1
 */
class Uploader
{
    /**
     * Сообщения об ошибках
     * @var array
     */
    protected $error_message = [];

    /**
     * Разрешенные для загрузки файлы
     * @var string
     */
    protected $allowed_types = 'gif|jpg|png|jpe|jpeg|avi|mp4|wmv|mpg|flv|mp3|swf|m4v|m4a|mov|3gp|f4v|mkv';

    /**
     * Максимальный размер файла
     * @uses 200x300 - задаете ширину и высоту оригинального изображения в формате ширина x высота
     * @uses 100x0   - допустимые размеры в пикселях ширины оригинального изображения
     * @uses 0x100   - допустимые размеры в пикселях высоты оригинального изображения
     * @uses 0       - если хотите чтобы изображение оставалось оригинальным.
     * @var string|array
     */
    protected $max_image_size = '1280x960';

    /**
     * Обработчик изоброжений
     * @uses GD, GD2, ImageMagick, NetPBM
     * @var string
     */
    protected $image_library = 'GD2';

    /**
     * Обработчик изоброжений
     * @uses GD, GD2, ImageMagick, NetPBM
     * @var string
     */
    protected $library_path = '/usr/bin';

    /**
     * Качество изоброжения после обработки
     * @var int
     */
    protected $quality = 80;

    /**
     * Размеры миниатур
     * @var array
     */
    protected $thumbs_size = [
        'small'     => '208x156',
        'medium'    => '432x324'
    ];

    /**
     * Разрешить наложения водиного знака
     * @var bool
     */
    protected $allow_watermark = true;

    /**
     * Минимальный размер для наложения водиного знака
     * @var string
     */
    protected $min_overlay_size = '640x480';

    /**
     * Минимальный размер для наложения водиного знака
     * @var string
     */
    protected $wm_image_light;

    /**
     * Минимальный размер для наложения водиного знака
     * @var string
     */
    protected $wm_image_dark;

    /**
     * Вы можете указать вертикальное смещение (в пикселях), чтобы применить для позиционирования водяного знака.
     * Смещение обычно перемещает водяной знак вниз, за исключением случаев, когда установлено пользовательское выравнивание “низ”,
     * тогда значение смещения будет перемещать водяной знак по направлению к верхней части изображения.
     * @var int
     */
    protected $wm_vrt_offset = 0;

    /**
     * Вы можете указать горизонтальное смещение (в пикселях), чтобы применить для позиционирования водяного знака.
     * Смещение обычно перемещает водяные знаки справа, за исключением случаев, когда установлено пользовательское выравнивание “право”,
     * тогда значение смещения будет перемещать водяной знак по направлению к левой части изображения.
     * @var int
     */
    protected $wm_hor_offset = 0;

    /**
     * Устанавливает вертикальное выравнивание для водяного знака.
     * @var string
     */
    protected $wm_vrt_alignment = 'bottom';

    /**
     * Задает выравнивание по горизонтали для водяного знака.
     * @var string
     */
    protected $wm_hor_alignment = 'right';

    /**
     * Upload constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->initialize($params);
    }

    /**
     * Инициализация библиотеки загрузки файлов
     * @param array $params
     */
    public function initialize($params = [])
    {
        $config = array_key_exists('upload', Engine::$config) ? Engine::$config['upload'] : [];

        $config['upload_path']      = Engine::$DT->config->item('cms.upload.dir');
        $config['allowed_types']    = is_string($config['allowed_types']) ? $config['allowed_types'] : $this->allow_watermark;
        $config['file_ext_tolower'] = true;

        // Определить максимально-разрешенное сервером размер загружаемого файла
        $upload_max_filesize = fn_upload_max_filesize();

        if( array_key_exists('max_size', $config) && $config['max_size'] > $upload_max_filesize ) {
            $config['max_size'] = $upload_max_filesize;
        }

        $config['encrypt_name']     = true;
        $config['remove_spaces']    = true;
        $config['detect_mime']      = true;
        $config['mod_mime_fix']     = true;
        $config['overwrite']        = false;

        if( array_key_exists('upload_path', $params) ) {
            $config['upload_path'] = $params['upload_path'];
        }

        Engine::$DT->load->library('upload', $config);
        Engine::$DT->upload->initialize($config, TRUE);

        $this->wm_image_light = Engine::$DT->config->item('cms.storage.dir') . 'wm_cms.png';
        $this->wm_image_dark  = Engine::$DT->config->item('cms.storage.dir') . 'wm_cms.png';

        if( array_key_exists('image', Engine::$config) )
        {
            if( array_key_exists('image_library', Engine::$config['image']) ) {
                $this->image_library = Engine::$config['image']['image_library'];
            }

            if( array_key_exists('image_library_path', Engine::$config['image']) ) {
                $this->library_path = Engine::$config['image']['image_library_path'];
            }

            if( array_key_exists('max_image_size', Engine::$config['image']) ) {
                $this->library_path = Engine::$config['image']['max_image_size'];
            }

            if( array_key_exists('quality', Engine::$config['image']) ) {
                $this->quality = Engine::$config['image']['quality'];
            }

            if( array_key_exists('thumbs_size_small', Engine::$config['image']) ) {
                $this->thumbs_size['small'] = Engine::$config['image']['thumbs_size_small'];
            }

            if( array_key_exists('thumbs_size_medium', Engine::$config['image']) ) {
                $this->thumbs_size['medium'] = Engine::$config['image']['thumbs_size_medium'];
            }

            if( array_key_exists('allow_watermark', Engine::$config['image']) ) {
                $this->allow_watermark = Engine::$config['image']['allow_watermark'];
            }

            if( array_key_exists('wm_min_overlay_size', Engine::$config['image']) ) {
                $this->min_overlay_size = Engine::$config['image']['wm_min_overlay_size'];
            }

            if( array_key_exists('wm_image_light', Engine::$config['image']) ) {
                $this->wm_image_light = Engine::$config['image']['wm_image_light'];
            }

            if( array_key_exists('wm_image_dark', Engine::$config['image']) ) {
                $this->wm_image_dark = Engine::$config['image']['wm_image_dark'];
            }

            if( array_key_exists('wm_vrt_offset', Engine::$config['image']) ) {
                $this->wm_vrt_offset = Engine::$config['image']['wm_vrt_offset'];
            }

            if( array_key_exists('wm_hor_offset', Engine::$config['image']) ) {
                $this->wm_hor_offset = Engine::$config['image']['wm_hor_offset'];
            }

            if( array_key_exists('wm_vrt_alignment', Engine::$config['image']) ) {
                $this->wm_vrt_alignment = Engine::$config['image']['wm_vrt_alignment'];
            }

            if( array_key_exists('wm_hor_alignment', Engine::$config['image']) ) {
                $this->wm_hor_alignment = Engine::$config['image']['wm_hor_alignment'];
            }
        }
    }

    /**
     * Загрузить фаил на сервер
     * @param string $field
     * @param array $output
     * @return false|array Информация о загруженным файле
     */
    public function doUpload($field = 'file', &$output = [])
    {
        if( !Engine::$DT->upload->do_upload($field) )
        {
            $this->error_message = Engine::$DT->upload->error_msg;
            $output = $this->error_message;
            return false;
        }


        $result = Engine::$DT->upload->data();
        $result['is_image'] && $this->handleImageUpload($result);
        $output = $this->error_message;

        return $result;
    }

    /**
     * Обработка изоброжения
     * @param array $result Данные о загруженном файле
     */
    protected function handleImageUpload(array &$result)
    {
        // Меняем размер изоброжения
        if( $this->max_image_size != 0 )
        {
            // Изменения размера изоброжения
            $image_size = explode('x', $this->max_image_size);

            $image_size[0] = intval($image_size[0]);
            $image_size[1] = intval($image_size[1]);

            if ( $image_size[0] < 10 ) $image_size[0] = 10;
            if ( $image_size[1] < 10 ) $image_size[1] = 10;

            $this->resize($image_size[0], $image_size[1], $result);
        }

        if( $this->allow_watermark ) {
            $this->watermark($result);
        }

        foreach ($this->thumbs_size as $marker =>  $size)
        {
            // Изменения размера изоброжения
            $image_size = explode('x', $size);

            $image_size[0] = intval($image_size[0]);
            $image_size[1] = intval($image_size[1]);

            if ( $image_size[0] < 10 ) $image_size[0] = 10;
            if ( $image_size[1] < 10 ) $image_size[1] = 10;

            $this->crop($image_size[0], $image_size[1], $marker, $result);
        }
    }

    /**
     * Изменения размера изоброжения
     * @param int $width
     * @param int $height
     * @param array $result
     */
    protected function resize($width, $height, array &$result)
    {
        $config['image_library']    = $this->image_library;
        $config['library_path']     = $this->library_path;
        $config['source_image']	    = $result['full_path'];
        $config['dynamic_output']	= FALSE;
        $config['quality']          = $this->quality;
        $config['new_image']        = $result['full_path'];
        $config['width']            = $width;
        $config['height']           = $height;
        $config['create_thumb']     = FALSE;
        $config['maintain_ratio']   = TRUE;
        $config['master_dim']       = 'auto';

        Engine::$DT->load->library('image_lib');
        Engine::$DT->image_lib->clear();
        Engine::$DT->image_lib->initialize($config);

        if( !Engine::$DT->image_lib->resize() )
        {
            Engine::$Log->critical('Не удалось изменить размер изоброжения');

            foreach (Engine::$DT->image_lib->error_msg as $message)
            {
                Engine::$Log->critical('Image resize: '. $message);
                $this->error_message[] = $message;
            }
        }

        list ( $result['image_width'], $result['image_height'] ) = getimagesize( $result['full_path'] );
    }

    /**
     * Создание уменьшенной копии изображения
     * @param int $width
     * @param int $height
     * @param string $marker
     * @param array $result
     */
    protected function crop($width, $height,  $marker, &$result)
    {
        $thumbs_path = reduce_double_slashes($result['file_path']  . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $marker);

        if( realpath($thumbs_path) === FALSE )
        {
            fn_mkdir($thumbs_path, DIR_WRITE_MODE);
            $thumbs_path = realpath($thumbs_path);
        }

        $config['image_library']    = $this->image_library;
        $config['library_path']     = $this->library_path;
        $config['source_image']	    = $result['full_path'];
        $config['dynamic_output']	= FALSE;
        $config['quality']          = 100;
        $config['new_image']        = reduce_double_slashes($thumbs_path . DIRECTORY_SEPARATOR . $result['file_name']);
        $config['width']            = $width;
        $config['height']           = $height;
        $config['create_thumb']     = TRUE;
        $config['maintain_ratio']   = TRUE;
        $config['thumb_marker']     = '';
        $config['master_dim']       = 'auto';

        Engine::$DT->load->library('image_lib');
        Engine::$DT->image_lib->clear();
        Engine::$DT->image_lib->initialize($config);

        if( !Engine::$DT->image_lib->resize() )
        {
            Engine::$Log->critical('Не удалось изменить размер изоброжения');

            foreach (Engine::$DT->image_lib->error_msg as $message)
            {
                Engine::$Log->critical('Image crop resize: '. $message);
                $this->error_message[] = $message;
            }
        }
        else
        {
            $result['thumbs_dir_path'][$marker]    = $thumbs_path;
            $result['thumbs_image_path'][$marker]  = Engine::$DT->image_lib->full_dst_path;
        }

    }

    /**
     * Наложение водиного знака
     * @param array $result
     * @return void
     */
    protected function watermark($result)
    {
        // Вычисления минимального размера для наложения водиного знака
        $cal_w_size = explode('x', $this->min_overlay_size);

        $wm_width   = intval($cal_w_size[0]);
        $wm_height  = intval($cal_w_size[1]);

        if( $wm_width === 0 && $wm_height > $result['image_height'] ) {
            return;
        }

        if( $wm_height === 0 && $wm_width > $result['image_width'] ) {
            return;
        }

        if( is_file($this->wm_image_light) === false || is_file($this->wm_image_dark) === false ) {
            $this->error_message[] = 'Путь к водяным знакам недействителен';
            return;
        }

        $watermark_image_light = realpath($this->wm_image_light);
        $watermark_image_dark  = realpath($this->wm_image_dark);

        list ( $watermark_width, $watermark_height ) = getimagesize( $watermark_image_light );


        // Вычисление положение водиного знака по оси y
        $watermark_y = $this->wm_vrt_offset;
        switch ($this->wm_vrt_alignment)
        {
            case "middle":
                $watermark_y = (( $result['image_height'] / 2 ) - ( $watermark_height / 2 ) + $this->wm_vrt_offset);
                break;
            case "bottom":
                $watermark_y = (($result['image_height'] - $watermark_height) - $this->wm_vrt_offset);
                break;
        }

        // Вычисление положение водиного знака по x
        $watermark_x = $this->wm_hor_offset;

        switch ($this->wm_hor_alignment)
        {
            case "center":
                $watermark_x = (( $result['image_width'] / 2 ) - ( $watermark_width / 2 ) + $this->wm_hor_offset);
                break;
            case "right":
                $watermark_x = (( $result['image_width'] - $watermark_width ) - $this->wm_hor_offset);
                break;
        }

        switch ($result['image_type'])
        {
            case "gif" :
                $image = @imagecreatefromgif( $result['full_path'] );
                break;
            case "jpeg" :
                $image = @imagecreatefromjpeg( $result['full_path'] );
                break;
            case "png" :
                $image = @imagecreatefrompng( $result['full_path'] );
                break;
        }

        $test = imagecreatetruecolor( 1, 1 );
        imagecopyresampled( $test, $image, 0, 0, $watermark_x, $watermark_y, 1, 1, $watermark_width, $watermark_height );
        $rgb = imagecolorat( $test, 0, 0 );

        $r = ($rgb >> 16) & 0xFF; $g = ($rgb >> 8) & 0xFF; $b = $rgb & 0xFF;

        $max = min( $r, $g, $b );
        $min = max( $r, $g, $b );
        $lightness = ( double ) (($max + $min) / 510.0);
        imagedestroy( $test );

        $watermark_image = ($lightness < 0.5) ? $watermark_image_light : $watermark_image_dark;

        $config['image_library']          = $this->image_library;
        $config['library_path']           = $this->library_path;
        $config['source_image']	          = $result['full_path'];
        $config['wm_overlay_path']	      = $watermark_image;
        $config['wm_type']                = 'overlay';
        $config['quality']                = 100;
        $config['wm_vrt_alignment']       = $this->wm_vrt_alignment;
        $config['wm_hor_alignment']       = $this->wm_hor_alignment;
        $config['wm_hor_offset']          = $this->wm_hor_offset;
        $config['wm_vrt_offset']          = $this->wm_vrt_offset;

        Engine::$DT->load->library('image_lib');
        Engine::$DT->image_lib->clear();
        Engine::$DT->image_lib->initialize($config);

        if( !Engine::$DT->image_lib->watermark() )
        {
            Engine::$Log->critical('Не удалось наложить водиной знак');

            foreach (Engine::$DT->image_lib->error_msg as $message)
            {
                Engine::$Log->critical('Image watermark: '. $message);
                $this->error_message[] = $message;
            }
        }
    }
}
<?php namespace DeftCMS\Libraries;

use DeftCMS\Engine;
use DeftCMS\Libraries\Image\ImageOptimizerDummy;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\OptimizerChainFactory;

defined('BASEPATH') || exit('No direct script access allowed');

/**
 * Библиотека для загрузки файлов и работы с изображениями
 *
 * @package     DeftCMS
 * @author	    b1tc0re
 * @copyright   2017-2022 DeftCMS (https://deftcms.ru/)
 * @since	    Version 0.0.9
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
     * Оптимизация изоброжений
     * @var OptimizerChain
     */
    protected $imageOptimizer;

    /**
     * Upload constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        $this->initialize($params);
    }

    /**
     * Получить сообщения об ошибках
     * @return array
     */
    public function getErrors()
    {
        return $this->error_message;
    }

    /**
     * Инициализация библиотеки загрузки файлов
     * @param array $params
     */
    public function initialize($params = [])
    {
        $upload = array_key_exists('upload', Engine::$config) ? Engine::$config['upload'] : [];

        if( array_key_exists('optimize_images', $upload) && ($upload['optimize_images'] === true || $upload['optimize_images'] == 1))
        {
            $this->imageOptimizer = OptimizerChainFactory::create();
        }
        else
        {
            $this->imageOptimizer = new ImageOptimizerDummy();
        }

        $this->imageOptimizer->setTimeout(30);

        $defaults = [
            'upload' => [
                'upload_path'       => Engine::$DT->config->item('cms.upload.dir'),
                'allowed_types'     => array_key_exists('allowed_types', $upload) && is_string($upload['allowed_types']) ? $upload['allowed_types'] : $this->allowed_types,
                'file_ext_tolower'  => true,
                'max_size'          => fn_upload_max_filesize(),
                'encrypt_name'      => true,
                'remove_spaces'     => true,
                'detect_mime'       => true,
                'mod_mime_fix'      => true,
                'overwrite'         => true,
            ],
            'images' => [
                'wm_image_light'    => Engine::$DT->config->item('cms.storage.dir') . 'wm_cms.png',
                'wm_image_dark'     => Engine::$DT->config->item('cms.storage.dir') . 'wm_cms.png',
                'image_library'     => $this->image_library,
                'library_path'      => $this->library_path,
                'quality'           => $this->quality,
                'thumbs_size'       => $this->thumbs_size,
                'allow_watermark'   => $this->allow_watermark,
                'min_overlay_size'  => $this->min_overlay_size,
                'wm_vrt_offset'     => $this->wm_vrt_offset,
                'wm_hor_offset'     => $this->wm_hor_offset,
                'wm_vrt_alignment'  => $this->wm_vrt_alignment,
                'wm_hor_alignment'  => $this->wm_hor_alignment,
                'max_image_size'    => $this->max_image_size
            ]
        ];

        $config = $defaults['upload'];

        foreach ($config as $item => $value)
        {
            if( array_key_exists('upload', $params) && array_key_exists($item, $params['upload']) ) {
                $config[$item] = $params['upload'][$item];
            }
            elseif( array_key_exists($item, $upload) ) {
                $config[$item] = $upload[$item];
            }
        }

        // Максимальный размер файла (в килобайтах)
        if( array_key_exists('max_size', $config)  ) {

            $config['max_size'] = fn_human_to_byte($config['max_size']);
            $config['max_size'] = round($config['max_size'] / 1000, 2);

            if( $config['max_size'] > $defaults['upload']['max_size'] ) {
                $config['max_size'] = $defaults['upload']['max_size'];
            }
        }

        Engine::$DT->load->library('upload', $config);
        Engine::$DT->upload->initialize($config, TRUE);

        $images = array_key_exists('images', Engine::$config) ? Engine::$config['images'] : [];

        foreach ($defaults['images'] as $item => $val)
        {
            if( array_key_exists('images', $params) && array_key_exists($item, $params['images']) ) {
                $this->{$item} = $params['images'][$item];
            }
            elseif(array_key_exists($item, $images)) {
                $this->{$item} = $images[$item];
            }
        }
    }

    /**
     * Загрузить фаилы на сервер
     * @param string $field
     * @param array $output
     * @return false|array Информация о загруженным файле
     */
    public function doUpload($field = 'file', &$output = [])
    {
        $result = [];

        if( isset($_FILES[$field]) && is_array($_FILES[$field]['name']) )
        {
            // Сохранить глобальный массив данных для восстоновления
            $FILES  = $_FILES;

            foreach ($this->getMultipleUpload()[$field] as $value)
            {
                $_FILES = [];
                $_FILES[$field] = $value;

                if( ($_result = $this->doUpload($field, $output)) === false )
                {
                    $_FILES = $_FILES;
                    return false;
                }

                $result[] = $_result;
            }

            $_FILES = $FILES;
            return $result;
        }

        if( !Engine::$DT->upload->do_upload($field) )
        {
            $this->error_message = Engine::$DT->upload->error_msg;
            $output[] = $this->error_message;
            return false;
        }

        $result = Engine::$DT->upload->data();

        if( $result['is_image'] )
        {
            $this->handleImageUpload($result);

            $result['file_size'] = round(filesize($result['full_path']) / 1024, 2);

            if( array_key_exists('thumbs', $result) )
            {
                foreach ($result['thumbs'] as $thumb)
                {
                    $this->imageOptimizer->optimize($thumb['image_path']);
                }
            }

            // Оптимизация изоброжений
            $this->imageOptimizer->useLogger(Engine::$Log);
            $this->imageOptimizer->optimize($result['full_path']);
        }

        $result['file_human_size'] = fn_human_file_size($result['file_size']);
        unset($result['image_size_str']);

        $output = $this->error_message;

        return $result;
    }

    /**
     * Получить размеры миниатур
     * @return array
     */
    public function getThumbsSizes()
    {
        return $this->thumbs_size;
    }

    /**
     * Создать миниатуры изоброжения
     * @param array $result
     */
    public function createThumbs(&$result)
    {
        foreach ($this->thumbs_size as $marker =>  $size)
        {
            // Изменения размера изоброжения
            $image_size = explode('x', $size);

            $image_size[0] = (int) $image_size[0];
            $image_size[1] = (int) $image_size[1];

            if ( $image_size[0] < 10 ) $image_size[0] = 10;
            if ( $image_size[1] < 10 ) $image_size[1] = 10;

            $this->crop($image_size[0], $image_size[1], $marker, $result);
        }
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

            $image_size[0] = (int) $image_size[0];
            $image_size[1] = (int) $image_size[1];

            if ( $image_size[0] < 10 ) $image_size[0] = 10;
            if ( $image_size[1] < 10 ) $image_size[1] = 10;

            $this->resize($image_size[0], $image_size[1], $result);
        }

        $this->createThumbs($result);

        if( $this->allow_watermark ) {
            $this->watermark($result);
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
            $result['thumbs'][$marker]['dir_path']      = $thumbs_path;
            $result['thumbs'][$marker]['image_path']    = Engine::$DT->image_lib->full_dst_path;
            $result['thumbs'][$marker]['file_size']     = filesize(Engine::$DT->image_lib->full_dst_path);
            $result['thumbs'][$marker]['width']         = Engine::$DT->image_lib->width;
            $result['thumbs'][$marker]['height']        = Engine::$DT->image_lib->height;
            $result['thumbs'][$marker]['url']           = base_url(fn_make_absolute_url(Engine::$DT->image_lib->full_dst_path));
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

        if( $wm_width === 0 || $wm_height > $result['image_height'] ) {
            return;
        }

        if( $wm_height === 0 || $wm_width > $result['image_width'] ) {
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

    /**
     * Получить данные для загрузки одновременно несколько файлов
     * @return array
     */
    protected function getMultipleUpload()
    {
        $result  = [];

        foreach($_FILES as $name => $file)
        {
            foreach($file as $property => $keys)
            {
                foreach($keys as $key => $value)
                {
                    $result[$name][$key][$property] = $value;
                }
            }
        }

        return $result;
    }
}
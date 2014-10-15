<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
use webnula2\common\Coordinate;

/**
 * Class Image
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{image}}", indexes={
 *  @Index(name="main", columns={"main"})
 * })
 */
final class Image extends Entity
{
	/**
	 * @Column(type="integer")
	 * @Id
	 */
	private $_id;

	/**
	 * @Column(type="integer", defaultValue=0)
	 */
	private $_sort;

	/**
	 * @Column(type="boolean", defaultValue=false)
	 */
	private $_main;

	/**
	 * @Column(type="string")
	 */
	private $_title;

	/**
	 * @Column(type="binary")
	 */
	private $_file = array();

	/**
	 * @param string $className
	 *
	 * @return \CActiveRecord
	 */
	public static function model( $className = __CLASS__ )
	{
		return parent::model( $className );
	}

	/**
	 * @param $filePath
	 * @param bool $deleteTempFile
	 *
	 * @return Image
	 * @throws \CDbException
	 * @throws \CHttpException
	 */
	public static function createFromFile( $filePath, $deleteTempFile = true )
	{
		$filePath = str_replace( DIRECTORY_SEPARATOR, '/', $filePath );

		if ( !is_file( $filePath ) ) {
			throw new \CHttpException( 404, strtr( "File {filepath} not found.", array( '{filepath}' => $filePath ) ) );
		}

		$webRoot = \Yii::getPathOfAlias( 'webroot' );
		$mediaPath = \Yii::getPathOfAlias( 'webroot.media.images' );
		self::mkDir( $mediaPath );


		$ext = \CFileHelper::getExtension( $filePath );

		$image = new Image();
		$image->setAttributes( array(
			'title' => basename( $filePath, '.' . $ext ),
			'sort' => $image->DbConnection->createCommand('SELECT MAX(sort) FROM '.$image->tableName())->queryScalar()+1,
			'main' => 0
		) );

		if ( $image->save() ) {
			$basename = basename( $filePath );
			$url = strtr( '/media/images/{date}/{id}/{file}', array( '{date}' => date( 'Y/m/d' ), '{id}' => $image->id, '{file}' => $basename ) );
			$destPath = strtr( '{media}{url}', array( '{media}' => $webRoot, '{url}' => $url ) );

			self::mkDir( dirname( $destPath ) );

			if ( copy( $filePath, $destPath ) ) {
				chmod( $destPath, 0666 );

				if ( $deleteTempFile ) {
					unlink( $filePath );
				}

				$image->setAttribute( 'file', array(
					'path' => $destPath,
					'url' => $url,
					'mime' => \CFileHelper::getMimeType( $filePath ),
					'ext' => $ext,
					'size' => filesize( $filePath ),
					'originalName' => $basename,
				) );
				$image->update();

				return $image;
			} else {
				$image->delete();
			}
		}

		return null;
	}

	/**
	 * @param $dir
	 * @param bool $recursive
	 */
	public static function mkDir( $dir, $recursive = true )
	{
		if ( !is_dir( $dir ) ) {
			mkdir( $dir, 0777, $recursive );
			chmod( $dir, 0777 );
		}
	}

	/**
	 * @return bool|void
	 * @throws \CDbException
	 */
	public function delete()
	{
		if ( isset( $this->file['path'] ) && is_file( $this->file['path'] ) ) {
			$filePath = dirname( $this->file['path'] );
			\CFileHelper::removeDirectory( $filePath );
		}

		return parent::delete();
	}

	/**
	 * @param $name
	 *
	 * @return Image
	 * @throws \CDbException
	 */
	public static function createFromUpload( $name )
	{
		$webRoot = \Yii::getPathOfAlias( 'webroot' );

		$mediaPath = \Yii::getPathOfAlias( 'webroot.media.images' );
		self::mkDir( $mediaPath );

		$uploadFile = \CUploadedFile::getInstanceByName( $name );

		$ext = \CFileHelper::getExtension( $uploadFile->name );

		$image = new Image();
		$image->setAttributes( array(
			'title' => basename( $uploadFile->name, '.' . $ext ),
			'sort' => $image->DbConnection->createCommand('SELECT MAX(sort) FROM '.$image->tableName())->queryScalar()+1,
			'main' => 0,
		) );

		if ( $image->save() ) {
			$basename = basename( $uploadFile->name );
			$url = strtr( '/media/images/{date}/{id}/{file}', array( '{date}' => date( 'Y/m/d' ), '{id}' => $image->id, '{file}' => $basename ) );
			$destPath = strtr( '{media}{url}', array( '{media}' => $webRoot, '{url}' => $url ) );

			self::mkDir( dirname( $destPath ) );

			if ( $uploadFile->saveAs( $destPath ) ) {

				chmod( $destPath, 0666 );

				$image->setAttribute( 'file', array(
					'path' => $destPath,
					'url' => $url,
					'ext' => $ext,
					'size' => $uploadFile->getSize(),
					'mime' => $uploadFile->getType(),
					'originalName' => $basename
				) );

				$image->update();
			} else {
				$image->delete();
			}

			return $image;
		}

		return null;
	}

	/**
	 * @param $name
	 *
	 * @return string
	 * @throws \CException
	 */
	public function getUrl( $name )
	{
		$filePath = dirname( $this->file['path'] );
		$filename = $this->file['originalName'];
		$url = dirname( $this->file['url'] );

		$destPath = $filePath . '/' . $name . '/' . $filename;
		$url = $url . '/' . $name . '/' . $filename;
		if ( !is_file( $destPath ) ) {
			if ( isset( \Yii::app()->params->imageSizes[$name] ) ) {
				$config = \Yii::app()->params->imageSizes[$name];
				$method = $config['method'];

				self::mkDir( $filePath . '/' . $name );

				$imagine = new Imagine();
				$source = $imagine->open( $this->file['path'] );

				if ( method_exists( $this, $method ) ) {
					$img = $this->$method( $source, $config );
				} else if ( method_exists( $source, $method ) ) {
					$img = $source->$method( $config );
				}

				if ( isset( $config['watermark'] ) ) {
					$watermark = $imagine->open( \Yii::getPathOfAlias( 'webroot' ) . $config['watermark'] );
					$size = $source->getSize();
					$wSize = $watermark->getSize();

					$x = $config['x'] ?: 'center';
					$y = $config['y'] ?: 'center';

					$x = Coordinate::fix( $x, $wSize->getWidth(), $size->getWidth() );
					$y = Coordinate::fix( $y, $wSize->getHeight(), $size->getHeight() );

					$img = $img->paste( $watermark, new Point( $x, $y ) );
				}

				$img->save( $destPath );
				chmod( $destPath, 0666 );
			}
		}

		return $url;
	}

	/**
	 * @param ImageInterface $image
	 * @param $config
	 *
	 * @return \Imagine\Image\ManipulatorInterface
	 */
	public function crop( ImageInterface $image, $config )
	{
		$w = intval( $config['width'] );
		$h = intval( $config['height'] );

		return $image->thumbnail( new Box( $w, $h ), ManipulatorInterface::THUMBNAIL_OUTBOUND );
	}

	/**
	 * @param ImageInterface $image
	 * @param $config
	 *
	 * @return mixed
	 */
	function resizeToFit( ImageInterface $image, $config )
	{
		$w = intval( $config['width'] );
		$h = intval( $config['height'] );

		$target = new Box( $w, $h );
		$orgSize = $image->getSize();
		if ( $orgSize->getWidth() > $orgSize->getHeight() ) {
			// Landscaped..
			$w = $orgSize->getWidth() * ( $target->getHeight() / $orgSize->getHeight() );
			$h = $target->getHeight();
			$cropBy = new Point( ( max( $w - $target->getWidth(), 0 ) ) / 2, 0 );
		} else {
			// Portrait..
			$w = $target->getWidth();
			$h = $orgSize->getHeight() * ( $target->getWidth() / $orgSize->getWidth() );
			$cropBy = new Point( 0, ( max( $h - $target->getHeight(), 0 ) ) / 2 );
		}

		$tempBox = Box( $w, $h );
		$img = $image->thumbnail( $tempBox, ImageInterface::THUMBNAIL_OUTBOUND );

		return $img->crop( $cropBy, $target );
	}

	/**
	 * @param ImageInterface $img
	 * @param $config
	 *
	 * @return \Imagine\Gd\Image|\Imagine\Image\ImageInterface
	 */
	function resizeToFill( ImageInterface $img, $config )
	{
		$img = $this->cropInset( $img, $config );

		$w = intval( $config['width'] );
		$h = intval( $config['height'] );

		$fcolor = $config['color'] ?: '#000000';
		$ftransparency = intval( $config['transparency'] ?: 100 );


		$size = new Box( $w, $h );
		$tsize = $img->getSize();
		$x = $y = 0;
		if ( $size->getWidth() > $tsize->getWidth() ) {
			$x = round( ( $size->getWidth() - $tsize->getWidth() ) / 2 );
		} elseif ( $size->getHeight() > $tsize->getHeight() ) {
			$y = round( ( $size->getHeight() - $tsize->getHeight() ) / 2 );
		}
		$pasteto = new Point( $x, $y );
		$imagine = new Imagine();
		$color = new Color( $fcolor, $ftransparency );
		$image = $imagine->create( $size, $color );

		$image->paste( $img, $pasteto );

		return $image;
	}

	/**
	 * @param ImageInterface $image
	 * @param $config
	 *
	 * @return \Imagine\Image\ManipulatorInterface
	 */
	public function cropInset( ImageInterface $image, $config )
	{
		$w = intval( $config['width'] );
		$h = intval( $config['height'] );

		return $image->thumbnail( new Box( $w, $h ), ManipulatorInterface::THUMBNAIL_INSET );
	}

	/**
	 *
	 */
	public function clear()
	{
		if ( isset( $this->file['path'] ) && is_file( $this->file['path'] ) ) {
			$dirname = dirname( $this->file['path'] );
			$params = \Yii::app()->getParams();

			if ( !empty( $params['imageSizes'] ) && is_array( $params['imageSizes'] ) ) {
				foreach ( $params['imageSizes'] as $name => $size ) {
					\CFileHelper::removeDirectory( $dirname . '/' . $name );
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function attributeLabels()
	{
		return array(
			'title' => \Yii::t( 'webnula2.locale', 'Title' ),
			'main' => \Yii::t( 'webnula2.locale', 'Main' ),
		);
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{image}}';
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if ( parent::beforeSave() ) {
			$this->file = \CJSON::encode( $this->file );
			return true;
		}

		return false;
	}

	/**
	 *
	 */
	public function afterSave()
	{
		parent::afterSave();
		$this->file = \CJSON::decode( $this->file );
	}

	/**
	 *
	 */
	public function afterFind()
	{
		parent::afterFind();
		$this->file = \CJSON::decode( $this->file );
	}

	/**
	 * @return array
	 */
	public function rules()
	{
		return array(
			array( 'id,sort', 'numerical', 'integerOnly' => true ),
			array( 'main', 'numerical', 'integerOnly' => true ),
			array( 'file', 'safe' ),
			array( 'title', 'length', 'max' => 255 )
		);
	}
}
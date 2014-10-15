<?php
/**
 * @author Martyushev Dmitriy (dangozero@gmail.com)
 * @copyright dangozero at gmail dot com
 * @license LICENSE
 */
namespace webnula2\models;


/**
 * Class File
 * @package webnula2\models
 *
 * @Entity
 * @Table(name="{{file}}")
 */
final class File extends Entity
{
	/**
	 * @Id
	 * @Column(type="integer")
	 */
	private $_id;

	/**
	 * @Column(type="integer", defaultValue=0)
	 */
	private $_sort = 0;

	/**
	 * @Column(type="string")
	 */
	private $_title = '';

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
	 * @return bool|mixed
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
		$mediaPath = \Yii::getPathOfAlias( 'webroot.media.files' );
		self::mkDir( $mediaPath );


		$ext = \CFileHelper::getExtension( $filePath );

		$file = new File();
		$file->setAttributes( array(
			'title' => basename( $filePath, '.' . $ext ),
			'sort' => 0,
			'main' => 0
		) );

		if ( $file->save() ) {
			$basename = basename( $filePath );
			$url = strtr( '/media/files/{date}/{id}/{file}', array( '{date}' => date( 'Y/m/d' ), '{id}' => $file->id, '{file}' => $basename ) );
			$destPath = strtr( '{media}{url}', array( '{media}' => $webRoot, '{url}' => $url ) );

			self::mkDir( dirname( $destPath ) );

			if ( copy( $filePath, $destPath ) ) {
				chmod( $destPath, 0666 );

				if ( $deleteTempFile ) {
					unlink( $filePath );
				}

				$file->setAttribute( 'file', array(
					'path' => $destPath,
					'url' => $url,
					'mime' => \CFileHelper::getMimeType( $filePath ),
					'ext' => $ext,
					'size' => filesize( $filePath ),
					'originalName' => $basename,
				) );
				$file->update();

				return $file;
			} else {
				$file->delete();
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
	 * @return bool
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
	 * @return array
	 * @throws \CDbException
	 */
	public static function createFromUpload( $name )
	{
		$webRoot = \Yii::getPathOfAlias( 'webroot' );

		$mediaPath = \Yii::getPathOfAlias( 'webroot.media.files' );
		self::mkDir( $mediaPath );

		$uploadFile = \CUploadedFile::getInstanceByName( $name );

		$ext = \CFileHelper::getExtension( $uploadFile->name );

		$file = new File();
		$file->setAttributes( array(
			'title' => basename( $uploadFile->name, '.' . $ext ),
			'sort' => 0,
			'main' => 0,
		) );

		if ( $file->save() ) {
			$basename = basename( $uploadFile->name );
			$url = strtr( '/media/files/{date}/{id}/{file}', array( '{date}' => date( 'Y/m/d' ), '{id}' => $file->id, '{file}' => $basename ) );
			$destPath = strtr( '{media}{url}', array( '{media}' => $webRoot, '{url}' => $url ) );

			self::mkDir( dirname( $destPath ) );

			if ( $uploadFile->saveAs( $destPath ) ) {

				chmod( $destPath, 0666 );

				$file->setAttribute( 'file', array(
					'path' => $destPath,
					'url' => $url,
					'ext' => $ext,
					'size' => $uploadFile->getSize(),
					'mime' => $uploadFile->getType(),
					'originalName' => $basename
				) );

				$file->update();
			} else {
				$file->delete();
			}

			return $file;
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function tableName()
	{
		return '{{file}}';
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
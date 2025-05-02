<?php

namespace MediaWiki\Extension\Yappin\Files;

use InvalidArgumentException;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\RequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use UploadBase;

/**
 * Class that should be initialised to handle a single file upload for a comment.
 *
 * This class essentially re-implements some similar functionality that exists for normal file uploads in MediaWiki.
 */
class CommentFileUpload extends UploadBase {
	/** @var UploadedFileInterface */
	protected $mUpload;

	/** @var RequestInterface */
	protected $mRequest;

	/**
	 * Initializes from a RequestInterface **not** a WebRequest instance unlike other sub-classes of UploadBase.
	 * @param RequestInterface &$request
	 */
	public function initializeFromRequest( &$request ) {
		$this->mRequest = $request;
		$this->initialize( $this->mRequest->getUploadedFiles()['file'] );
	}

	/**
	 * Initializes the new upload of the file
	 * @param UploadedFileInterface $upload
	 * @return void
	 */
	private function initialize( $upload ) {
		$this->mUpload = $upload;
		$filename = $this->generateFilename( $this->mUpload->getClientFilename() );
		$this->initializePathInfo(
			$filename,
			$upload->getStream()->getMetadata( 'uri' ),
			$upload->getSize()
		);
	}

	private function generateFilename( $initialName ) {
		[ $partname, $ext ] = self::splitExtensions( $initialName );

		if ( $ext !== [] ) {
			$this->mFinalExtension = trim( end( $ext ) );
		} else {
			throw new InvalidArgumentException( 'No valid file extension' );
		}

		// Generate a unique ID for the upload, rather than using the existing file name
		$id = MediaWikiServices::getInstance()->getGlobalIdGenerator()->newUUIDv4();
		return "$id." . $ext;
	}

	/**
	 * @param WebRequest $request
	 * @return bool
	 */
	public static function isValidRequest( $request ) {
		# Allow all requests, even if no file is present, so that an error
		# because a post_max_size or upload_max_filesize overflow
		return true;
	}

	/**
	 * @return string
	 */
	public function getSourceType() {
		return 'file';
	}

	/**
	 * Returns whether this upload failed because of overflow of a maximum set
	 * in php.ini
	 *
	 * @see WebRequestUpload::isIniSizeOverflow()
	 * @return bool
	 */
	public function isIniSizeOverflow() {
		if ( $this->mUpload->getError() == UPLOAD_ERR_INI_SIZE ) {
			# PHP indicated that upload_max_filesize is exceeded
			return true;
		}

		$contentLength = $this->mRequest->getHeader( 'Content-Length' );
		$maxPostSize = wfShorthandToInteger( ini_get( 'post_max_size' ), 0 );

		if ( $maxPostSize && $contentLength > $maxPostSize ) {
			# post_max_size is exceeded
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function verifyUpload() {
		# Check for a post_max_size or upload_max_size overflow, so that a
		# proper error can be shown to the user
		if ( $this->mTempPath === null || $this->isEmptyFile() ) {
			if ( $this->isIniSizeOverflow() ) {
				return [
					'status' => UploadBase::FILE_TOO_LARGE,
					'max' => min(
						self::getMaxUploadSize( $this->getSourceType() ),
						self::getMaxPhpUploadSize()
					),
				];
			}
		}

		return parent::verifyUpload();
	}

	public function validateName() {
		if ( !is_string( $this->mDesiredDestName ) ) {
			return [ 'status' => self::ILLEGAL_FILENAME ];
		}

		// Don't allow users to override the list of prohibited file extensions (check file extension)
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$checkFileExtensions = $config->get( MainConfigNames::CheckFileExtensions );
		$strictFileExtensions = $config->get( MainConfigNames::StrictFileExtensions );
		$fileExtensions = $config->get( MainConfigNames::FileExtensions );
		$prohibitedFileExtensions = $config->get( MainConfigNames::ProhibitedFileExtensions );

		$badList = self::checkFileExtensionList( [ $this->mFinalExtension ], $prohibitedFileExtensions );

		if ( $badList ||
			( $checkFileExtensions && $strictFileExtensions &&
				!self::checkFileExtension( $this->mFinalExtension, $fileExtensions ) )
		) {
			return [ 'status' => self::FILETYPE_BADTYPE ];
		}

		return true;
	}
}

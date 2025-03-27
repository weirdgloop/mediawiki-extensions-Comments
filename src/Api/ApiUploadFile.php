<?php

namespace MediaWiki\Extension\Comments\Api;

use MediaWiki\Extension\Comments\Files\CommentFileService;
use MediaWiki\Extension\Comments\Files\CommentFileUpload;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\SimpleHandler;
use UploadBase;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class ApiUploadFile extends SimpleHandler {
	/** @var CommentFileService */
	private $commentFileService;

	/**
	 * @param CommentFileService $commentFileService
	 */
	public function __construct( $commentFileService ) {
		$this->commentFileService = $commentFileService;
	}

	public function run() {
		// First, perform similar checks that MW core would do for file uploads.
		if ( !UploadBase::isEnabled() ) {
			throw new LocalizedHttpException( new MessageValue( 'uploaddisabledtext' ) );
		}
		$permError = $this->commentFileService->isAllowedToUpload( $this->getAuthority() );
		if ( $permError !== true ) {
			throw new LocalizedHttpException( $permError, 403 );
		}

		$request = $this->getRequest();
		$upload = new CommentFileUpload();
		$upload->initializeFromRequest( $request );

		// Check the uploaded file
		$verification = $upload->verifyUpload();
		if ( $verification['status'] !== UploadBase::OK ) {
			throw new LocalizedHttpException(
				$this->getVerificationError( $verification ), 400 );
		}

		return true;
	}

	/**
	 * @see \ApiUpload::checkVerification
	 * @param array $verification
	 * @return MessageValue
	 */
	private function getVerificationError( $verification ) {
		switch ( $verification[ 'status' ] ) {
			case UploadBase::FILETYPE_MISSING:
				$msg = 'filetype-missing';
				break;
			case UploadBase::EMPTY_FILE:
				$msg = 'empty-file';
				break;
			case UploadBase::FILE_TOO_LARGE:
				$msg = 'file-too-large';
				break;
			case UploadBase::FILETYPE_BADTYPE:
				$msg = 'filetype-banned';
				break;
			case UploadBase::VERIFICATION_ERROR:
				$msg = 'verification-error';
				break;
			default:
				return new MessageValue(
					'comments-upload-error-unknown', [ $verification['status'] ] );
		}
		return new MessageValue( $msg );
	}

	/**
	 * @inheritDoc
	 */
	public function getParamSettings() {
		return [
			'file' => [
				self::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'upload',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}

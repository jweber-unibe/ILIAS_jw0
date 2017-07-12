<?php

namespace ILIAS\FileUpload;

use ILIAS\Filesystem\Exception\IOException;
use ILIAS\Filesystem\Filesystems;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\FileUpload\Collection\EntryLockingStringMap;
use ILIAS\FileUpload\Collection\ImmutableMapWrapper;
use ILIAS\FileUpload\DTO\Metadata;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Exception\IllegalStateException;
use ILIAS\FileUpload\Processor\PreProcessor;
use ILIAS\FileUpload\Processor\PreProcessorManager;
use ILIAS\HTTP\GlobalHttpState;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Class FileUploadImpl
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @since 5.3
 * @version 1.0.0
 */
final class FileUploadImpl implements FileUpload {

	/**
	 * @var PreProcessorManager $processorManager
	 */
	private $processorManager;
	/**
	 * @var Filesystems $filesystems
	 */
	private $filesystems;
	/**
	 * @var GlobalHttpState
	 */
	private $globalHttpState;
	/**
	 * @var bool $processed
	 */
	private $processed;
	/**
	 * @var bool $moved
	 */
	private $moved;
	/**
	 * @var UploadResult[] $uploadResult
	 */
	private $uploadResult;
	/**
	 * @var StreamInterface[] $uploadStreams
	 */
	private $uploadStreams;




	/**
	 * FileUploadImpl constructor.
	 *
	 * @param PreProcessorManager $processorManager The processor manager which should be used.
	 * @param Filesystems         $filesystems      The Filesystems implementation which should be used.
	 * @param GlobalHttpState     $globalHttpState  The http implementation which should be used to detect the uploaded files.
	 */
	public function __construct(PreProcessorManager $processorManager, Filesystems $filesystems, GlobalHttpState $globalHttpState) {
		$this->processorManager = $processorManager;
		$this->filesystems = $filesystems;
		$this->globalHttpState = $globalHttpState;
		$this->processed = false;
		$this->moved = false;
		$this->uploadResult = [];
	}


	/**
	 * @inheritDoc
	 */
	public function moveFilesTo($destination, $location = Location::STORAGE) {
		
		if($this->processed === false) {
			throw new \RuntimeException('Can not move unprocessed files.');
		}

		if($this->moved === true) {
			throw new \RuntimeException('Can not move the files a second time.');
		}

		$filesystem = $this->selectFilesystem($location);
		$tempResults = [];

		foreach ($this->uploadResult as $key => $uploadResult) {

			try {
				$path = $destination . '/' . $uploadResult->getName();
				$filesystem->writeStream($path, Streams::ofPsr7Stream($this->uploadStreams[$key]));
				$tempResults[] = $this->regenerateUploadResultWithPath($uploadResult, $path);
			}
			catch (IOException $ex) {
				$this->regenerateUploadResultWithCopyError($uploadResult, $ex->getMessage());
			}
		}

		$this->uploadResult = $tempResults;
		$this->uploadStreams = NULL;
		$this->moved = true;
	}


	/**
	 * Generate an exact copy of the result with the given path.
	 *
	 * @param UploadResult $result  The result which should be cloned.
	 * @param string       $path    The path which should be set on the result clone.
	 *
	 * @return UploadResult         The cloned result with the given path.
	 */
	private function regenerateUploadResultWithPath(UploadResult $result, $path) {
		return new UploadResult(
			$result->getName(),
			$result->getSize(),
			$result->getMimeType(),
			$result->getMetaData(),
			$result->getStatus(),
			$path
		);
	}


	/**
	 * Creates a clone of the given result and set the status to rejected with the passed error message.
	 *
	 * @param UploadResult $result          The result which should be cloned.
	 * @param string       $errorReason     The reason why the error occurred.
	 *
	 * @return UploadResult                 The newly cloned rejected result.
	 */
	private function regenerateUploadResultWithCopyError(UploadResult $result, $errorReason) {
		return new UploadResult(
			$result->getName(),
			$result->getSize(),
			$result->getMimeType(),
			$result->getMetaData(),
			new ProcessingStatus(ProcessingStatus::REJECTED, $errorReason),
			''
		);
	}


	/**
	 * Selects the correct filesystem by the given Location constant.
	 *
	 * @param int $location The storage location constant defined within the Location interface.
	 *
	 * @return \ILIAS\Filesystem\Filesystem
	 *
	 * @see Location
	 *
	 * @throws \InvalidArgumentException    Thrown if the location is not a valid Location constant.
	 */
	private function selectFilesystem($location) {
		switch ($location) {
			case Location::CUSTOMIZING:
				return $this->filesystems->customizing();
			case Location::STORAGE:
				return $this->filesystems->storage();
			case Location::WEB:
				return $this->filesystems->web();
			default:
				throw new \InvalidArgumentException("No filesystem found for location code \"$location\"");
		}
	}


	/**
	 * @inheritDoc
	 */
	public function uploadSizeLimit() {
		return \ilUtil::getUploadSizeLimitBytes();
	}


	/**
	 * @inheritDoc
	 */
	public function register(PreProcessor $preProcessor) {
		if($this->processed === false)
			$this->processorManager->with($preProcessor);
		else
			throw new IllegalStateException('Can not register processor after the upload was processed.');
	}


	/**
	 * @inheritDoc
	 */
	public function process() {
		if($this->processed === true)
			throw new IllegalStateException('Can not reprocess the uploaded files.');

		/**
		 * @var UploadedFileInterface[] $uploadedFiles
		 */
		$uploadedFiles = $this->globalHttpState->request()->getUploadedFiles();
		foreach ($uploadedFiles as $file) {

			$metadata = new Metadata($file->getClientFilename(), $file->getSize(), $file->getClientMediaType());
			$stream = $file->getStream();
			$this->uploadStreams[] = $stream;

			if($file->getError() === UPLOAD_ERR_OK) {
				$processingResult = $this->processorManager->process($stream, $metadata);
				$result = new UploadResult(
					$metadata->getFilename(),
					$metadata->getUploadSize(),
					$metadata->getMimeType(),
					new ImmutableMapWrapper($metadata->additionalMetaData()),
					$processingResult,
					''
				);
				$this->uploadResult[] = $result;
			}
			else
			{
				$this->rejectFailedUpload($metadata);
			}
		}

		$this->processed = true;
	}


	/**
	 * Reject a failed upload with the given metadata.
	 *
	 * @param Metadata $metadata    The metadata used to create the rejected result.
	 * @return void
	 */
	private function rejectFailedUpload(Metadata $metadata) {
		//reject failed upload
		$processingStatus = new ProcessingStatus(ProcessingStatus::REJECTED, 'Upload failed');
		$extraMetadata = new ImmutableMapWrapper(new EntryLockingStringMap());
		$result = new UploadResult(
			$metadata->getFilename(),
			$metadata->getUploadSize(),
			$metadata->getMimeType(),
			$extraMetadata,
			$processingStatus,
			''
		);

		$this->uploadResult[] = $result;
	}


	/**
	 * @inheritDoc
	 */
	public function getResults() {
		return $this->uploadResult;
	}
}
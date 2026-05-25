<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Controller;

use OCA\ShareReview\Service\ShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use Psr\Log\LoggerInterface;

class OutputController extends Controller {
	private $logger;
	private $ShareService;

	public function __construct(
		string          $appName,
		IRequest        $request,
		LoggerInterface $logger,
		ShareService    $ShareService
	) {
		parent::__construct($appName, $request);
		$this->logger = $logger;
		$this->ShareService = $ShareService;
	}

	/**
	 * get the data when requested from internal page
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function read() {
		if ($this->ShareService->isSecured() !== true) {
			return new DataResponse('', HTTP::STATUS_FORBIDDEN);
		}
		$data = $this->ShareService->read(false);
		return new DataResponse($data, HTTP::STATUS_OK);
	}

	/**
	 * get the data when requested from internal page
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function readNew() {
		if (!$this->ShareService->isSecured()) {
			return new DataResponse('', HTTP::STATUS_FORBIDDEN);
		}
		$data = $this->ShareService->read(true);
		return new DataResponse($data, HTTP::STATUS_OK);
	}

	/**
	 * get the data when requested from internal page
	 *
	 * @NoAdminRequired
	 * @param $shareId
	 * @return DataResponse
	 * @throws ShareNotFound
	 */
	public function delete($shareId) {
		if (!$this->ShareService->isSecured()) {
			return new DataResponse('', HTTP::STATUS_FORBIDDEN);
		}
		$result = $this->ShareService->delete($shareId);
		return new DataResponse($result, HTTP::STATUS_OK);
	}

	/**
	 * confirm shares by setting the current timestamp
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 * @throws ShareNotFound
	 */
	public function confirm() {
		if (!$this->ShareService->isSecured()) {
			return new DataResponse('', HTTP::STATUS_FORBIDDEN);
		}
		$result = $this->ShareService->confirm(time());
		return new DataResponse($result, HTTP::STATUS_OK);
	}

	/**
	 * reset the confirmation timestamp
	 *
	 * @NoAdminRequired
	 * @return DataResponse
	 * @throws ShareNotFound
	 */
        public function confirmReset() {
                if (!$this->ShareService->isSecured()) {
                        return new DataResponse('', HTTP::STATUS_FORBIDDEN);
                }
                $result = $this->ShareService->confirm(0);
                return new DataResponse($result, HTTP::STATUS_OK);
        }

       /**
        * persist talk share visibility
        *
        * @NoAdminRequired
        * @return DataResponse
        */
       public function showTalk($state) {
               if (!$this->ShareService->isSecured()) {
                       return new DataResponse('', HTTP::STATUS_FORBIDDEN);
               }
			   $state = $state === 'true';
			   $result = $this->ShareService->showTalk($state);
               return new DataResponse($result, HTTP::STATUS_OK);
       }

	/**
	 * app can only be used when it is restricted to at least one group for security reasons
	 *
	 * @NoAdminRequired
	 * @return DataResponse|true
	 */
	private function checkSecured() {
		if ($this->ShareService->isSecured() !== true) {
			return new DataResponse('', HTTP::STATUS_FORBIDDEN);
		}
		return new DataResponse(true, HTTP::STATUS_OK);
	}
}
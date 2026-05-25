<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Controller;

use OCA\ShareReview\BackgroundJob\GenerateReportJob;
use OCA\ShareReview\Service\ReportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\IUserSession;

class ReportController extends Controller {
    private ReportService $reportService;
	private IAppConfig $appConfig;
    private IJobList $jobList;
    private IUserSession $userSession;

    public function __construct(string $appName,
                                IRequest $request,
                                ReportService $reportService,
								IAppConfig $appConfig,
                                IJobList $jobList,
                                IUserSession $userSession) {
        parent::__construct($appName, $request);
        $this->reportService = $reportService;
		$this->appConfig = $appConfig;
        $this->jobList = $jobList;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     */
    public function export(string $path, string $type = 'pdf'): DataResponse {
        $name = $this->reportService->generate($path, $type);
        return new DataResponse(['file' => $name], Http::STATUS_OK);
    }

    /**
     * @NoAdminRequired
     */
    public function saveSettings(string $folder, string $schedule, string $type = 'pdf'): DataResponse {
        $user = $this->userSession->getUser();
        $this->appConfig->setValueString('sharereview', 'folderOwner', $user->getUID());
        $this->appConfig->setValueString('sharereview', 'reportFolder', $folder);
        $this->appConfig->setValueString('sharereview', 'schedule', $schedule);
        $this->appConfig->setValueString('sharereview', 'reportType', $type);

        $this->jobList->remove(GenerateReportJob::class);
        if ($schedule !== 'none') {
            $this->jobList->add(GenerateReportJob::class);
        }

        return new DataResponse(['status' => 'ok'], Http::STATUS_OK);
    }
}

<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\BackgroundJob;

use OCP\BackgroundJob\TimedJob;
use OCP\IAppConfig;
use OCA\ShareReview\Service\ReportService;
use OCP\AppFramework\Utility\ITimeFactory;
use Psr\Log\LoggerInterface;

class GenerateReportJob extends TimedJob {
	private IAppConfig $appConfig;
	private ReportService $reportService;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $time,
								IAppConfig $appConfig,
								ReportService $reportService,
								LoggerInterface $logger) {
		parent::__construct($time);
		$this->appConfig = $appConfig;
		$this->reportService = $reportService;
		$this->logger = $logger;
		$this->setInterval($this->getInterval());
	}

	public function run($argument): void {
		$interval = $this->getInterval();
		if ($interval === 0) {
			return;
		}
		$this->setInterval($interval);
		$this->logger->info("Generating Share Review report in the background");
		$this->reportService->generateDefault();
	}

	public function getInterval(): int {
		$schedule = $this->appConfig->getValueString('sharereview', 'schedule', 'none');
		return match ($schedule) {
			'daily' => 60 * 60 * 24,
			'weekly' => 60 * 60 * 24 * 7,
			'monthly' => 60 * 60 * 24 * 30,
			default => 0,
		};
	}
}

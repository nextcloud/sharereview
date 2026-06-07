<?php
/**
 * Share Review
 *
 * SPDX-FileCopyrightText: 2024-2026 Marcel Scherello
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareReview\Service;

use OCP\Files\IRootFolder;
use OCP\IUserSession;
use OCP\IAppConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class ReportService {
	private ShareService $shareService;
	private IRootFolder $rootFolder;
	private IUserSession $userSession;
	private IAppConfig $appConfig;
	private IL10N $l10n;
	/** @var LoggerInterface */
	private $logger;

	public function __construct(
		ShareService    $shareService,
		IRootFolder     $rootFolder,
		IUserSession    $userSession,
		IAppConfig      $appConfig,
		IL10N           $l10n,
		LoggerInterface $logger
	) {
		$this->shareService = $shareService;
		$this->rootFolder = $rootFolder;
		$this->userSession = $userSession;
		$this->appConfig = $appConfig;
		$this->l10n = $l10n;
		$this->logger = $logger;
	}

	/**
	 * Generate report and store in the given folder for the given user
	 */
	public function generate(string $folder, string $type = 'pdf', ?string $uid = null): string {
		if ($uid === null) {
			$uid = $this->userSession->getUser()->getUID();
		}

		$data = $this->shareService->read(false, $uid);

		if ($type === 'csv') {
			$content = $this->buildCsv($data);
			$extension = 'csv';
		} else {
			$content = $this->buildPdf($data);
			$extension = 'pdf';
		}

		$userFolder = $this->rootFolder->getUserFolder($uid);
		$target = $userFolder->get($folder);
		$fileName = 'Share Review - Report ' . date('YmdHis') . '.' . $extension;
		$file = $target->newFile($fileName);
		$file->putContent($content);

		return $fileName;
	}

	/**
	 * Generate report using stored default folder
	 */
	public function generateDefault(): void {
		$owner = $this->appConfig->getValueString('sharereview', 'folderOwner', '');
		$folder = $this->appConfig->getValueString('sharereview', 'reportFolder', '');
		$type = $this->appConfig->getValueString('sharereview', 'reportType', 'pdf');
		if ($owner === '' || $folder === '') {
			return;
		}
		$this->generate($folder, $type, $owner);
	}

	private function buildCsv(array $rows): string {
		$lines = [];
		$lines[] = implode(',', [
			$this->l10n->t('App'),
			$this->l10n->t('Item'),
			$this->l10n->t('Initiator'),
			$this->l10n->t('Type'),
			$this->l10n->t('Permissions'),
			$this->l10n->t('Time')
		]);
		foreach ($rows as $row) {
			[$shareType, $recipient] = array_pad(explode(';', (string)$row['type'], 2), 2, '');
			$typeText = $this->formatType((int)$shareType, $recipient);
			[$permission, $password, $expiration] = explode(';', (string)$row['permissions']);
			$permText = $this->formatPermission((int)$permission, $password, $expiration);
			$timeText = $this->formatTime((string)$row['time']);
			$lines[] = implode(',', [
				$this->escapeCsv((string)$row['app']),
				$this->escapeCsv($this->formatObject((string)$row['object'])),
				$this->escapeCsv((string)$row['initiator']),
				$this->escapeCsv($typeText),
				$this->escapeCsv($permText),
				$this->escapeCsv($timeText),
			]);
		}
		return implode("\n", $lines);
	}

	private function escapeCsv(string $text): string {
		$escaped = str_replace('"', '""', $text);
		if (str_contains($text, ',') || str_contains($text, '"') || str_contains($text, "\n")) {
			return '"' . $escaped . '"';
		}
		return $escaped;
	}

	private function buildPdf(array $rows): string {
		$header = [];
		$header[] = 'Share Review Report';
		$header[] = 'Audit date: ' . (new \DateTime())->format('d.m.Y, H:i:s');
		$header[] = '';
		$header[] = $this->formatRow([
			$this->l10n->t('App'),
			$this->l10n->t('Item')
		], [15, 105]);
		$header[] = $this->formatRow([
			'',
			$this->l10n->t('Initiator'),
			$this->l10n->t('Type'),
			$this->l10n->t('Permissions'),
			$this->l10n->t('Time')
		], [15, 20, 33, 42, 20]);
		$header[] = '';

		$body = [];
		foreach ($rows as $row) {
			[$shareType, $recipient] = array_pad(explode(';', (string)$row['type'], 2), 2, '');
			$typeText = $this->formatType((int)$shareType, $recipient);
			[$permission, $password, $expiration] = explode(';', (string)$row['permissions']);
			$permText = $this->formatPermission((int)$permission, $password, $expiration);
			$timeText = $this->formatTime((string)$row['time']);
			$body[] = $this->formatRow([
				(string)$row['app'],
				$this->formatObject((string)$row['object'])
			], [15, 105]);
			$body[] = $this->formatRow([
				'',
				(string)$row['initiator'],
				$typeText,
				$permText,
				$timeText,
			], [15, 20, 33, 42, 20]);
			$body[] = '';
		}

		$fontSize = 9;
		$lineHeight = 11;
		$width = 842;
		$height = 595;
		$leftMargin = 36;
		$topMargin = 40;
		$bottomMargin = 40;

		$linesPerPage = intdiv($height - $topMargin - $bottomMargin, $lineHeight);
		$usableLines = $linesPerPage - count($header);
		$pages = [];
		for ($i = 0; $i < count($body); $i += $usableLines) {
			$pages[] = array_merge($header, array_slice($body, $i, $usableLines));
		}
		if ($pages === []) {
			$pages[] = $header;
		}

		$objects = [];
		$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$pageNums = [];

		$fontObjNum = 3;
		$objects[$fontObjNum] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';
		$nextObj = $fontObjNum + 1;

		foreach ($pages as $pageLines) {
			$contentStream = "BT\n/F1 {$fontSize} Tf\n{$leftMargin} " . ($height - $topMargin) . " Td\n";
			foreach ($pageLines as $line) {
				$contentStream .= '(' . $this->escapePdfText($line) . ") Tj\n0 -{$lineHeight} Td\n";
			}
			$contentStream .= "ET";
			$length = strlen($contentStream);

			$contentNum = $nextObj++;
			$objects[$contentNum] = "<< /Length {$length} >>\nstream\n{$contentStream}\nendstream";

			$pageNum = $nextObj++;
			$objects[$pageNum] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$width} {$height}] /Contents {$contentNum} 0 R /Resources << /Font << /F1 {$fontObjNum} 0 R >> >> >>";
			$pageNums[] = $pageNum;
		}

		$objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn($n
			) => "$n 0 R", $pageNums)) . '] /Count ' . count($pageNums) . ' >>';

		ksort($objects);
		$pdf = "%PDF-1.4\n";
		$offsets = [0];
		for ($i = 1; $i <= count($objects); $i++) {
			$offsets[$i] = strlen($pdf);
			$pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
		}
		$xrefPos = strlen($pdf);
		$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
		$pdf .= "0000000000 65535 f \n";
		for ($i = 1; $i <= count($objects); $i++) {
			$pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
		}
		$pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefPos}\n%%EOF";

		return $pdf;
	}

	private function formatPermission(int $permission, $password, $expiration): string {
		$parts = [];
		if ($permission & 1)  $parts[] = $this->l10n->t('Read');
		if ($permission & 2)  $parts[] = $this->l10n->t('Update');
		if ($permission & 4)  $parts[] = $this->l10n->t('Create');
		if ($permission & 8)  $parts[] = $this->l10n->t('Delete');
		if ($permission & 16) $parts[] = $this->l10n->t('Re-share');
		$label = $parts !== [] ? implode(', ', $parts) : $this->l10n->t('None');

		$passLabel = $password !== '' ? ' ' . $this->l10n->t('Password protected') : '';
		$expLabel = $expiration !== '' ? ' ' . $expiration : '';
		return $label . $passLabel . $expLabel;
	}

	private function formatType(int $type, string $recipient): string {
		switch ($type) {
			case 12:
				$label = $this->l10n->t('Deck');
				break;
			case 10:
				$label = $this->l10n->t('Talk room');
				break;
			case 7:
				$label = $this->l10n->t('Team');
				break;
			case 9:
			case 6:
				$label = $this->l10n->t('Federation');
				break;
			case 4:
				$label = $this->l10n->t('E-mail');
				break;
			case 3:
				$label = $this->l10n->t('Link');
				break;
			case 1:
				$label = $this->l10n->t('Group');
				break;
			case 0:
				$label = $this->l10n->t('User');
				break;
			default:
				$label = $this->l10n->t('Other');
				break;
		}
		return $label . ': ' . $recipient;
	}

	private function formatObject(string $object): string {
		if ($object === '') {
			return '';
		}

		return explode(';', $object, 2)[0];
	}

	private function escapePdfText(string $text): string {
		return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
	}

	private function formatRow(array $fields, array $widths): string {
		$parts = [];
		foreach ($fields as $i => $value) {
			$parts[] = str_pad(substr($value, 0, $widths[$i]), $widths[$i]);
		}
		return implode(' ', $parts);
	}

	private function formatTime(string $time): string {
		try {
			$dt = new \DateTime($time);
		} catch (\Exception $e) {
			$dt = new \DateTime();
		}
		return $dt->format('d.m.Y, H:i:s');
	}
}

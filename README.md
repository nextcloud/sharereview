# Share Review
This app can be used for auditing shares within a Nextcloud instance e.g. for data loss prevention.
Share status can be exported for documentation.

- Review any file share 
  - files, talk, deck, teams, federation
  - app specific shares (if implemented by other apps)
- Remove shares
- Confirm current review
  - show only new shares next time
- Audit compliance
- Assign review to user groups (e.g. audit or risk mgmt)
- Export as CSV or PDF (manual or regular background job)

### Note:
The app must be restricted to at least one specific user group in the app store. 
This prevents accidental exposure of the shared content to all users.

<p align="center">
<img src="https://github.com/Rello/sharereview/blob/main/screenshots/logo.png?raw=true" alt="Main" width="600" title="Share Review">
</p>
<p align="center">
<img src="https://github.com/Rello/sharereview/blob/main/screenshots/screenshot.png?raw=true" alt="Main" width="600" title="Share Review">
</p>
<p align="center">
<img src="https://github.com/Rello/sharereview/blob/main/screenshots/report.png?raw=true" alt="Main" width="600" title="Share Review">
</p>

## Register shares from another app

Other Nextcloud apps can add their own share types to Share Review by listening for
`OCA\ShareReview\Sources\SourceEvent`. The registered source class is resolved from
Nextcloud's dependency injection container when Share Review loads the share list.

### 1. Register an event listener

Register the listener in the external app's `Application::register()` method:

```php
use OCA\MyApp\ShareReview\ShareReviewListener;
use OCA\ShareReview\Sources\SourceEvent;

public function register(IRegistrationContext $context): void {
	$context->registerEventListener(SourceEvent::class, ShareReviewListener::class);
}
```

### 2. Register the source class

The listener adds the source class to the event:

```php
namespace OCA\MyApp\ShareReview;

use OCA\ShareReview\Sources\SourceEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class ShareReviewListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof SourceEvent) {
			return;
		}

		$event->registerSource(ShareReviewSource::class);
	}
}
```

### 3. Implement the source

The source class must provide these methods:

```php
namespace OCA\MyApp\ShareReview;

use OCA\ShareReview\Sources\ISource;

class ShareReviewSource implements ISource {
	public function getName(): string {
		return 'MyApp';
	}

	public function getShares(): array {
		return [
			[
				'id' => 123, // Unique app-specific identifier passed to deleteShare().
				'object' => 'Example object', // Display name, such as a file path or report name.
				'initiator' => 'alice', // User ID of the initiator.
				'type' => 0, // One of the OCP\Share\IShare type constants.
				'recipient' => 'bob', // User ID, group ID, email address, or link token.
				'permissions' => 1, // Permission bitmask. Use 1 as the default if not set.
				'password' => true, // Whether the share is password protected. Do not return the password.
				'expiration' => '2026-12-31', // Optional expiration date displayed for the share.
				'time' => '2026-05-31 12:00:00', // Creation time. Use '1970-01-01 01:00:00' if null.
				'action' => '', // Optional deletion identifier override. Empty uses id.
			],
		];
	}

	public function deleteShare(string $shareId): bool {
		// Delete the app-specific share and return whether deletion succeeded.
		return true;
	}
}
```

See the
[Analytics integration](https://github.com/rello/analytics/tree/master/lib/ShareReview)
for a working implementation.

## Maintainers
- [Marcel Scherello](https://github.com/rello) (author, project leader)

## Support
Thank you to PhpStorm from [JetBrains](https://www.jetbrains.com/?from=AudioPlayerforNextcloudandownCloud) <br>
<img src="https://raw.githubusercontent.com/rello/analytics/master/screenshots/jetbrains.svg" alt="Main" width="100" title="Analytics">

---

[![Version](https://img.shields.io/github/release/rello/sharereview.svg)](https://github.com/rello/sharereview/blob/master/CHANGELOG.md)&#160;[![License: AGPLv3](https://img.shields.io/badge/license-AGPLv3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)&#160;&#160;&#160;[![Bitcoin](https://img.shields.io/badge/donate-Bitcoin-blue.svg)](https://github.com/rello/audioplayer/wiki/donate)&#160;[![PayPal](https://img.shields.io/badge/donate-PayPal-blue.svg)](https://github.com/rello/audioplayer/wiki/donate)

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
`OCP\Share\ShareReview\RegisterShareReviewSourceEvent` (available since Nextcloud
34.0.2). The registered source class is resolved from Nextcloud's dependency
injection container when Share Review loads the share list.

### 1. Register an event listener

Register the listener in the external app's `Application::register()` method:

```php
use OCA\MyApp\ShareReview\ShareReviewListener;
use OCP\Share\ShareReview\RegisterShareReviewSourceEvent;

public function register(IRegistrationContext $context): void {
	$context->registerEventListener(RegisterShareReviewSourceEvent::class, ShareReviewListener::class);
}
```

### 2. Register the source class

The listener adds the source class to the event:

```php
namespace OCA\MyApp\ShareReview;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\ShareReview\RegisterShareReviewSourceEvent;

class ShareReviewListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof RegisterShareReviewSourceEvent) {
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

use OCP\Share\ShareReview\IShareReviewSource;
use OCP\Share\ShareReview\ShareReviewEntry;
use OCP\Share\ShareReview\ShareReviewPermission;

class ShareReviewSource implements IShareReviewSource {
	public const PERMISSION_READ = 'myapp:read';
	public const PERMISSION_PUBLISH = 'myapp:publish';

	public function getName(): string {
		return 'MyApp';
	}

	/** @return list<ShareReviewEntry> */
	public function getShares(): array {
		return [
			new ShareReviewEntry(
				id: '123', // Unique app-specific identifier passed to deleteShare().
				object: 'Example object', // Display name, such as a file path or report name.
				initiator: 'alice', // User ID of the initiator.
				type: 0, // One of the OCP\Share\IShare type constants.
				recipient: 'bob', // User ID, group ID, email address, or link token.
				lastModifiedTimestamp: 1748685600, // Unix timestamp of creation or last modification, whichever is later. Pass 0 if not tracked.
				permissions: [ // Granted permissions. Every ID must be namespaced with your own
					// app ID ("<appId>:<permission>") — apps never share identifiers, even for
					// permissions with the same name. Translate labels and hints with your own
					// app's IL10N: the app owning a permission also owns its wording.
					new ShareReviewPermission(self::PERMISSION_READ, $this->l->t('Read'), priority: 80),
					new ShareReviewPermission(self::PERMISSION_PUBLISH, $this->l->t('Publish'), $this->l->t('Publish the object to the portal'), 30),
				],
				hasPassword: true, // Whether the share is password protected. Never the password itself.
				expirationTimestamp: 1767139200, // Optional expiration Unix timestamp of the share.
			),
		];
	}

	public function deleteShare(string $shareId): bool {
		// Dispatch OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent first and
		// delete only when access was granted — see the event's documentation.
		// Then delete the app-specific share and return whether deletion succeeded.
		return true;
	}
}
```

See the
[Analytics integration](https://github.com/rello/analytics/tree/master/lib/ShareReview)
for a working implementation.

### Legacy API (deprecated)

The app-local `OCA\ShareReview\Sources\SourceEvent`/`ISource` extension API keeps
working, but is deprecated. It is required only on Nextcloud < 34.0.2, where the
`OCP\Share\ShareReview` classes do not exist. It will be removed once this app's
minimum supported server version is raised to Nextcloud 34 or later. If both APIs
register a source with the same name, the OCP-registered source wins.

## Maintainers
- [Marcel Scherello](https://github.com/rello) (author, project leader)

## Support
Thank you to PhpStorm from [JetBrains](https://www.jetbrains.com/?from=AudioPlayerforNextcloudandownCloud) <br>
<img src="https://raw.githubusercontent.com/rello/analytics/master/screenshots/jetbrains.svg" alt="Main" width="100" title="Analytics">

---

[![Version](https://img.shields.io/github/release/rello/sharereview.svg)](https://github.com/rello/sharereview/blob/master/CHANGELOG.md)&#160;[![License: AGPLv3](https://img.shields.io/badge/license-AGPLv3-blue.svg)](http://www.gnu.org/licenses/agpl-3.0)&#160;&#160;&#160;[![Bitcoin](https://img.shields.io/badge/donate-Bitcoin-blue.svg)](https://github.com/rello/audioplayer/wiki/donate)&#160;[![PayPal](https://img.shields.io/badge/donate-PayPal-blue.svg)](https://github.com/rello/audioplayer/wiki/donate)

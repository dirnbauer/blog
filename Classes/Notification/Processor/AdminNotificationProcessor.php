<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/blog.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\AgencyPack\Blog\Notification\Processor;

use Psr\Http\Message\ServerRequestInterface;
use T3G\AgencyPack\Blog\Mail\MailMessage;
use T3G\AgencyPack\Blog\Notification\CommentAddedNotification;
use T3G\AgencyPack\Blog\Notification\NotificationInterface;
use T3G\AgencyPack\Blog\Utility\RequestUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AdminNotificationProcessor implements ProcessorInterface
{
    public function process(ServerRequestInterface $request, NotificationInterface $notification): void
    {
        $notificationId = $notification->getNotificationId();

        if ($notificationId === CommentAddedNotification::class) {
            $this->processCommentAddNotification($request, $notification);
        }
    }

    protected function processCommentAddNotification(ServerRequestInterface $request, NotificationInterface $notification): void
    {
        $settings = RequestUtility::getSiteSettings($request);

        if ((bool)($settings->get('plugin.tx_blog.settings.notifications.CommentAddedNotification.admin.enable') ?? false)) {
            $emailAddresses = GeneralUtility::trimExplode(',', (string)($settings->get('plugin.tx_blog.settings.notifications.CommentAddedNotification.admin.email') ?? ''));
            $mail = GeneralUtility::makeInstance(MailMessage::class);
            $mail
                ->setSubject($notification->getTitle())
                ->setBody($notification->getMessage())
                ->setFrom([(string)($settings->get('plugin.tx_blog.settings.notifications.email.senderMail') ?? '')])
                ->setTo($emailAddresses)
                ->send();
        }
    }
}

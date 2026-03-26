<?php

/**
 * @package    Livingword.Plugin.Task
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Plugin\Task\Livingword\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmemailHelper;
use CWM\Component\Livingword\Site\Helper\CwmpartnerHelper;
use CWM\Component\Livingword\Site\Helper\CwmprogressHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use CWM\Component\Livingword\Site\Helper\CwmscriptureHelper;
use CWM\Component\Livingword\Site\Helper\CwmuserHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;

/**
 * Task plugin for sending daily reading email notifications.
 *
 * @since  5.0.0
 */
class Livingword extends CMSPlugin implements SubscriberInterface
{
    use TaskPluginTrait;
    use DatabaseAwareTrait;

    /**
     * @var string[]
     * @since 5.0.0
     */
    protected const TASKS_MAP = [
        'livingword.email_notifications' => [
            'langConstPrefix' => 'PLG_TASK_LIVINGWORD_EMAIL',
            'method'          => 'sendEmailNotifications',
        ],
        'livingword.weekly_digest' => [
            'langConstPrefix' => 'PLG_TASK_LIVINGWORD_WEEKLY_DIGEST',
            'method'          => 'sendWeeklyDigest',
        ],
        'livingword.partner_digest' => [
            'langConstPrefix' => 'PLG_TASK_LIVINGWORD_PARTNER_DIGEST',
            'method'          => 'sendPartnerDigest',
        ],
    ];

    /**
     * @return  array
     *
     * @since   5.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onTaskOptionsList'    => 'advertiseRoutines',
            'onExecuteTask'        => 'standardRoutineHandler',
            'onContentPrepareForm' => 'enhanceTaskItemForm',
        ];
    }

    /**
     * Send email notifications to subscribed users with today's reading.
     *
     * @param   ExecuteTaskEvent  $event  The task event
     *
     * @return  int  Task status code
     *
     * @since   5.0.0
     */
    private function sendEmailNotifications(ExecuteTaskEvent $event): int
    {
        $params = ComponentHelper::getParams('com_livingword');

        if (!(int) $params->get('config_enable_email', 1)) {
            return Status::OK;
        }

        $db = $this->getDatabase();

        // Get users subscribed to email whose preferred hour matches now
        $serverHour = (int) date('G');

        $query = $db->getQuery(true)
            ->select('a.*, u.name, u.email AS user_email')
            ->from($db->quoteName('#__livingword_users', 'a'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
            ->where($db->quoteName('a.email') . ' = 1')
            ->where($db->quoteName('u.block') . ' = 0')
            ->where($db->quoteName('a.email_hour') . ' = ' . $serverHour);

        $db->setQuery($query);
        $subscribers = $db->loadObjectList();

        if (empty($subscribers)) {
            return Status::OK;
        }

        $defaultVersion = $params->get('config_bible_version', 'kjv');
        $siteName       = $this->getApplication()->get('sitename');
        $sent           = 0;

        foreach ($subscribers as $sub) {
            $planId  = (int) $sub->plan_id;
            $version = $sub->bible_version ?: $defaultVersion;

            $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $planId);
            $currentDay = CwmreadingHelper::getCurrentReadingDay($sub->start_date ?? date('Y-01-01'), (int) $sub->date_offset, $totalDays ?: 365);
            $reading    = CwmreadingHelper::getReadingForDay($db, $planId, $currentDay);

            if (!$reading) {
                continue;
            }

            // Ensure tokens exist
            $token          = CwmuserHelper::ensureUnsubscribeToken($db, (int) $sub->user_id);
            $unsubscribeUrl = CwmuserHelper::getUnsubscribeUrl($token);
            $actionToken    = CwmuserHelper::ensureActionToken($db, (int) $sub->user_id);
            $completeUrl    = CwmuserHelper::getCompleteReadingUrl($actionToken);

            $content = '<h2 style="margin:0 0 12px;font-size:1.1em;color:#333;">Today\'s Reading (Day ' . $currentDay . ')</h2>'
                     . CwmscriptureHelper::buildEmailContent($reading->reading, $version)
                     . CwmemailHelper::button($completeUrl, 'Mark as Read', '#198754');

            $body = CwmemailHelper::wrapLayout($content, $siteName, $unsubscribeUrl);

            try {
                CwmemailHelper::send(
                    $sub->user_email,
                    $siteName . ' - Daily Bible Reading',
                    $body,
                    $sub->name,
                    $unsubscribeUrl
                );
                $sent++;
            } catch (\Exception $e) {
                $this->getApplication()->getLogger()->error('LivingWord email failed for user ' . $sub->user_id . ': ' . $e->getMessage());
            }
        }

        $this->logTask('Sent ' . $sent . ' email notifications.');

        return Status::OK;
    }

    /**
     * Send weekly progress digest to opted-in users.
     *
     * @param   ExecuteTaskEvent  $event  The task event
     *
     * @return  int  Task status code
     *
     * @since   5.8.0
     */
    private function sendWeeklyDigest(ExecuteTaskEvent $event): int
    {
        $db       = $this->getDatabase();
        $siteName = $this->getApplication()->get('sitename');

        $query = $db->getQuery(true)
            ->select('a.*, u.name, u.email AS user_email')
            ->from($db->quoteName('#__livingword_users', 'a'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.user_id'))
            ->where($db->quoteName('a.email') . ' = 1')
            ->where($db->quoteName('u.block') . ' = 0');

        $db->setQuery($query);
        $subscribers = $db->loadObjectList() ?: [];
        $sent        = 0;
        $weekAgo     = date('Y-m-d H:i:s', strtotime('-7 days'));

        foreach ($subscribers as $sub) {
            $userId = (int) $sub->user_id;
            $planId = (int) $sub->plan_id;

            $query = $db->getQuery(true)
                ->select('COUNT(DISTINCT ' . $db->quoteName('day') . ')')
                ->from($db->quoteName('#__livingword_progress'))
                ->where($db->quoteName('user_id') . ' = ' . $userId)
                ->where($db->quoteName('plan_id') . ' = ' . $planId)
                ->where($db->quoteName('completed_at') . ' >= ' . $db->quote($weekAgo));
            $db->setQuery($query);
            $weeklyCount = (int) $db->loadResult();

            $totalDays      = CwmreadingHelper::getPlanTotalDays($db, $planId);
            $completedCount = CwmprogressHelper::getCompletedCount($db, $userId, $planId);
            $progressPct    = ($totalDays > 0) ? round(($completedCount / $totalDays) * 100) : 0;
            $streak         = (int) ($sub->streak_current ?? 0);

            $expectedDay = CwmreadingHelper::getCurrentReadingDay($sub->start_date ?? date('Y-01-01'), 0, $totalDays ?: 365);
            $pace        = $completedCount - $expectedDay;
            $paceText    = ($pace >= 0) ? ($pace . ' days ahead') : (abs($pace) . ' days behind');

            $encouragement = ($streak >= 7) ? 'Amazing consistency! Keep it up!'
                : (($weeklyCount >= 5) ? 'Great week of reading!'
                : (($weeklyCount > 0) ? 'Every reading counts — keep going!'
                : 'A new week is a fresh start. You\'ve got this!'));

            $content = '<h2 style="margin:0 0 12px;font-size:1.1em;color:#333;">Your Weekly Reading Summary</h2>'
                     . CwmemailHelper::statsTableOpen()
                     . CwmemailHelper::statRow('Readings this week', (string) $weeklyCount)
                     . CwmemailHelper::statRow('Overall progress', $completedCount . ' / ' . $totalDays . ' (' . $progressPct . '%)')
                     . CwmemailHelper::statRow('Current streak', $streak . ' days')
                     . CwmemailHelper::statRow('Pace', $paceText)
                     . CwmemailHelper::statsTableClose()
                     . '<p style="font-style:italic;color:#555;">' . htmlspecialchars($encouragement, ENT_QUOTES, 'UTF-8') . '</p>';

            $token          = CwmuserHelper::ensureUnsubscribeToken($db, $userId);
            $unsubscribeUrl = CwmuserHelper::getUnsubscribeUrl($token);
            $body           = CwmemailHelper::wrapLayout($content, $siteName, $unsubscribeUrl);

            try {
                CwmemailHelper::send(
                    $sub->user_email,
                    $siteName . ' - Your Weekly Reading Summary',
                    $body,
                    $sub->name,
                    $unsubscribeUrl
                );
                $sent++;
            } catch (\Exception $e) {
                $this->getApplication()->getLogger()->error(
                    'LivingWord weekly digest failed for user ' . $userId . ': ' . $e->getMessage()
                );
            }
        }

        $this->logTask('Sent ' . $sent . ' weekly digest emails.');

        return Status::OK;
    }

    /**
     * Send weekly partner progress digest emails.
     *
     * @param   ExecuteTaskEvent  $event  The task event
     *
     * @return  int  Task status code
     *
     * @since   5.6.0
     */
    private function sendPartnerDigest(ExecuteTaskEvent $event): int
    {
        $db       = $this->getDatabase();
        $siteName = $this->getApplication()->get('sitename');
        $pairs    = CwmpartnerHelper::getPartnerPairsForEmail($db);
        $sent     = 0;

        foreach ($pairs as $userRow) {
            $userId    = (int) $userRow->user_id;
            $partnerId = (int) $userRow->accountability_partner_id;

            if (!CwmpartnerHelper::isMutualPartnership($db, $userId, $partnerId)) {
                continue;
            }

            $partnerProgress = CwmpartnerHelper::getPartnerProgress($db, $userId);

            if (!$partnerProgress || !$partnerProgress->shares_progress) {
                continue;
            }

            $content = '<h2 style="margin:0 0 12px;font-size:1.1em;color:#333;">Your Accountability Partner\'s Progress</h2>'
                     . '<p><strong>' . htmlspecialchars($partnerProgress->partner_name, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                     . CwmemailHelper::statsTableOpen()
                     . CwmemailHelper::statRow('Plan', $partnerProgress->plan_name)
                     . CwmemailHelper::statRow('Progress', $partnerProgress->completed_count . ' of ' . $partnerProgress->total_days . ' (' . $partnerProgress->progress_percent . '%)')
                     . CwmemailHelper::statRow('Current Day', 'Day ' . $partnerProgress->current_day)
                     . CwmemailHelper::statRow('Current Streak', $partnerProgress->streak_current . ' days')
                     . CwmemailHelper::statRow('Best Streak', $partnerProgress->streak_best . ' days')
                     . CwmemailHelper::statsTableClose();

            $token          = CwmuserHelper::ensureUnsubscribeToken($db, $userId);
            $unsubscribeUrl = CwmuserHelper::getUnsubscribeUrl($token);
            $body           = CwmemailHelper::wrapLayout($content, $siteName, $unsubscribeUrl);

            try {
                CwmemailHelper::send(
                    $userRow->user_email,
                    $siteName . ' - Your Partner\'s Reading Progress',
                    $body,
                    $userRow->name,
                    $unsubscribeUrl
                );
                $sent++;
            } catch (\Exception $e) {
                $this->getApplication()->getLogger()->error(
                    'LivingWord partner digest failed for user ' . $userId . ': ' . $e->getMessage()
                );
            }
        }

        $this->logTask('Sent ' . $sent . ' partner digest emails.');

        return Status::OK;
    }
}

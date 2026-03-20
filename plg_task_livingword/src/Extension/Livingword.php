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

use CWM\Component\Livingword\Site\Helper\CwmbiblegatewayHelper;
use CWM\Component\Livingword\Site\Helper\CwmreadingHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Mail\MailerFactoryInterface;
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

        // Get all users subscribed to email
        $query = $db->getQuery(true)
            ->select('a.*, u.name, u.email AS user_email')
            ->from($db->quoteName('#__livingword', 'a'))
            ->join('INNER', $db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('a.userid'))
            ->where($db->quoteName('a.email') . ' = 1')
            ->where($db->quoteName('u.block') . ' = 0');

        $db->setQuery($query);
        $subscribers = $db->loadObjectList();

        if (empty($subscribers)) {
            return Status::OK;
        }

        $defaultPlan    = $params->get('config_bible_plan', 'comp');
        $defaultVersion = $params->get('config_bible_version', 'NLT');
        $siteName       = $this->getApplication()->get('sitename');
        $sent           = 0;

        foreach ($subscribers as $sub) {
            $plan    = $sub->bibleplan ?: $defaultPlan;
            $version = $sub->bibleversion ?: $defaultVersion;

            $totalDays  = CwmreadingHelper::getPlanTotalDays($db, $plan);
            $currentDay = CwmreadingHelper::getCurrentReadingDay($sub->startdate ?? date('Y-01-01'), (int) $sub->dateoffset, $totalDays ?: 365);
            $reading    = CwmreadingHelper::getReadingForDay($db, $plan, $currentDay);

            if (!$reading) {
                continue;
            }

            $passageText = CwmbiblegatewayHelper::parseReadingReference($reading->reading);
            $readingUrl  = CwmbiblegatewayHelper::buildReadingUrl($passageText, $version);

            $body = '<p>Today\'s reading (Day ' . $currentDay . '):</p>'
                  . '<p><a href="' . htmlspecialchars($readingUrl, ENT_QUOTES, 'UTF-8') . '">'
                  . htmlspecialchars($passageText, ENT_QUOTES, 'UTF-8') . '</a></p>'
                  . '<p>From ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . '</p>';

            try {
                $mailer = $this->getApplication()->getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer->addRecipient($sub->user_email, $sub->name);
                $mailer->setSubject($siteName . ' - Daily Bible Reading');
                $mailer->setBody($body);
                $mailer->isHtml(true);
                $mailer->send();
                $sent++;
            } catch (\Exception $e) {
                $this->getApplication()->getLogger()->error('LivingWord email failed for user ' . $sub->userid . ': ' . $e->getMessage());
            }
        }

        $this->logTask('Sent ' . $sent . ' email notifications.');

        return Status::OK;
    }
}

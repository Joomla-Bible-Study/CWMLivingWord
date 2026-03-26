<?php

/**
 * @package    Livingword.Admin
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

/**
 * Group form controller
 *
 * @since  5.7.0
 */
class CwmgroupController extends FormController
{
    /**
     * Update a group member's role (leader/member).
     *
     * @return  void
     *
     * @since   5.7.0
     */
    public function updateMemberRole(): void
    {
        Session::checkToken('get') or die;

        $input    = $this->input;
        $memberId = $input->getInt('member_id', 0);
        $role     = $input->getCmd('role', 'member');
        $groupId  = $input->getInt('id', 0);

        /** @var \CWM\Component\Livingword\Administrator\Model\CwmgroupModel $model */
        $model = $this->getModel();
        $model->updateMemberRole($memberId, $role);

        $this->setRedirect(
            Route::_('index.php?option=com_livingword&view=cwmgroup&layout=edit&id=' . $groupId, false)
        );
    }

    /**
     * Send invite emails for a group. Returns JSON.
     *
     * @return  void
     *
     * @since   5.4.0
     */
    public function sendInvites(): void
    {
        $app = $this->app;
        $app->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (!Session::checkToken('get')) {
            $app->sendHeaders();
            echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
            $app->close();
        }

        $groupId    = $this->input->getInt('id', 0);
        $emailsRaw  = $this->input->getString('emails', '');

        if ($groupId <= 0 || empty($emailsRaw)) {
            $app->sendHeaders();
            echo new JsonResponse(null, Text::_('COM_LIVINGWORD_INVITES_INVALID'), true);
            $app->close();
        }

        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select([$db->quoteName('name'), $db->quoteName('invite_token')])
            ->from($db->quoteName('#__livingword_groups'))
            ->where($db->quoteName('id') . ' = ' . $groupId);

        $db->setQuery($query);
        $group = $db->loadObject();

        if (!$group || empty($group->invite_token)) {
            $app->sendHeaders();
            echo new JsonResponse(null, Text::_('COM_LIVINGWORD_GROUP_NOT_FOUND'), true);
            $app->close();
        }

        $inviteUrl = Uri::root() . 'index.php?option=com_livingword&task=cwmgroup.join&token=' . urlencode($group->invite_token);
        $siteName  = $app->get('sitename');

        // Parse comma or semicolon separated emails
        $emails = preg_split('/[,;\s]+/', $emailsRaw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = array_filter($emails, function ($e) {
            return filter_var(trim($e), FILTER_VALIDATE_EMAIL);
        });

        if (empty($emails)) {
            $app->sendHeaders();
            echo new JsonResponse(null, Text::_('COM_LIVINGWORD_INVITES_NO_VALID_EMAILS'), true);
            $app->close();
        }

        $sent   = 0;
        $failed = 0;

        $subject = Text::sprintf('COM_LIVINGWORD_INVITE_EMAIL_SUBJECT', $siteName, $group->name);
        $body    = Text::sprintf('COM_LIVINGWORD_INVITE_EMAIL_BODY', $group->name, $siteName)
                 . '<p style="margin:20px 0;text-align:center;">'
                 . '<a href="' . htmlspecialchars($inviteUrl, ENT_QUOTES, 'UTF-8') . '" '
                 . 'style="display:inline-block;padding:10px 24px;background:#0d6efd;color:#fff;'
                 . 'text-decoration:none;border-radius:6px;font-weight:600;">'
                 . Text::_('COM_LIVINGWORD_INVITE_JOIN_BUTTON') . '</a></p>';

        foreach ($emails as $email) {
            $email = trim($email);

            try {
                $mailer = $app->getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer->addRecipient($email);
                $mailer->setSubject($subject);
                $mailer->setBody($body);
                $mailer->isHtml(true);
                $mailer->send();
                $sent++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        $message = Text::sprintf('COM_LIVINGWORD_INVITES_RESULT', $sent, \count($emails));

        $app->sendHeaders();
        echo new JsonResponse(['sent' => $sent, 'failed' => $failed], $message);
        $app->close();
    }
}

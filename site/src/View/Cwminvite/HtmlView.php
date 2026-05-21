<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       https://www.christianwebministries.org
 */

namespace CWM\Component\Livingword\Site\View\Cwminvite;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use CWM\Component\Livingword\Site\Helper\CwmgroupHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Uri\Uri;

/**
 * Public group invitation landing page.
 *
 * Visitors arrive here from a shared invite link (?view=cwminvite&token=...).
 * Renders without requiring authentication so non-registered users can see
 * what they're being invited to and choose login / register.
 *
 * @since  5.8.0
 */
class HtmlView extends BaseHtmlView
{
    /** @var ?object @since 5.8.0 */
    protected ?object $group = null;

    /** @var string @since 5.8.0 */
    protected string $token = '';

    /** @var bool @since 5.8.0 */
    protected bool $isGuest = true;

    /** @var bool @since 5.8.0 */
    protected bool $alreadyMember = false;

    /** @var bool @since 5.8.0 */
    protected bool $registrationAllowed = false;

    /** @var string @since 5.8.0 */
    protected string $loginUrl = '';

    /** @var string @since 5.8.0 */
    protected string $registerUrl = '';

    /**
     * @param   string  $tpl  Template name.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.8.0
     */
    #[\Override]
    public function display($tpl = null): void
    {
        $app         = Factory::getApplication();
        $this->token = trim((string) $app->getInput()->getString('token', ''));

        /** @var \CWM\Component\Livingword\Site\Model\CwminviteModel $model */
        $model       = $this->getModel();
        $this->group = $model->getGroupByToken($this->token);

        $identity      = $app->getIdentity();
        $userId        = (int) ($identity?->id ?? 0);
        $this->isGuest = $userId === 0;

        if ($this->group && !$this->isGuest) {
            $db                  = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
            $this->alreadyMember = CwmgroupHelper::isMember($db, (int) $this->group->id, $userId);
        }

        $usersConfig               = ComponentHelper::getParams('com_users');
        $this->registrationAllowed = (bool) $usersConfig->get('allowUserRegistration', 0);

        $returnUrl         = Uri::root()
            . 'index.php?option=com_livingword&view=cwminvite&token=' . urlencode($this->token);
        $returnEncoded     = base64_encode($returnUrl);
        $this->loginUrl    = Uri::root() . 'index.php?option=com_users&view=login&return=' . $returnEncoded;
        $this->registerUrl = Uri::root() . 'index.php?option=com_users&view=registration&return=' . $returnEncoded;

        $this->prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document title and metadata.
     *
     * @return  void
     *
     * @throws  \Exception
     * @since   5.8.0
     */
    protected function prepareDocument(): void
    {
        $app   = Factory::getApplication();
        $title = $this->group
            ? Text::sprintf('COM_LIVINGWORD_INVITE_PAGE_TITLE', $this->group->name)
            : Text::_('COM_LIVINGWORD_INVITE_PAGE_TITLE_DEFAULT');

        if ($app->get('sitename_pagetitles', 0) === 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) === 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->getDocument()->setTitle($title);
    }
}

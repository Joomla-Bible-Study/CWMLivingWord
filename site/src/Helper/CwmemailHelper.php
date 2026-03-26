<?php

/**
 * @package    Livingword.Site
 * @copyright  (C) 2026 CWM Team All rights reserved
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CWM\Component\Livingword\Site\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Mail\MailerFactoryInterface;

/**
 * Shared email helper for building and sending templated emails.
 *
 * All outgoing emails use a consistent branded layout configured via
 * component options: logo, organization name, brand color, and footer.
 *
 * @since  5.4.0
 */
class CwmemailHelper
{
    /**
     * Get email branding settings from component config.
     *
     * @return  object  {logo, orgName, color, footer}
     *
     * @since   5.4.0
     */
    public static function getBranding(): object
    {
        $params   = ComponentHelper::getParams('com_livingword');
        $siteName = Factory::getApplication()->get('sitename');

        return (object) [
            'logo'    => $params->get('config_email_logo', ''),
            'orgName' => $params->get('config_email_org_name', '') ?: $siteName,
            'color'   => $params->get('config_email_color', '#0d6efd'),
            'footer'  => $params->get('config_email_footer', ''),
        ];
    }

    /**
     * Wrap body content in the branded email layout.
     *
     * Pulls logo, organization name, brand color, and footer from
     * component configuration. All emails share this consistent shell.
     *
     * @param   string   $content         The main email body HTML
     * @param   string   $siteName        Site name for fallback
     * @param   ?string  $unsubscribeUrl  Optional unsubscribe link for footer
     * @param   ?string  $footerExtra     Optional extra HTML before the unsubscribe link
     *
     * @return  string  Complete HTML email body
     *
     * @since   5.4.0
     */
    public static function wrapLayout(
        string $content,
        string $siteName,
        ?string $unsubscribeUrl = null,
        ?string $footerExtra = null
    ): string {
        $brand = self::getBranding();
        $color = htmlspecialchars($brand->color, ENT_QUOTES, 'UTF-8');
        $org   = htmlspecialchars($brand->orgName ?: $siteName, ENT_QUOTES, 'UTF-8');

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
              . '<meta name="viewport" content="width=device-width,initial-scale=1"></head>'
              . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#333;">'
              . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:20px 0;">'
              . '<tr><td align="center">'
              . '<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);">';

        // ── Branded header bar ──
        $html .= '<tr><td style="background:' . $color . ';padding:16px 32px;text-align:center;">';

        if (!empty($brand->logo)) {
            $html .= '<img src="' . htmlspecialchars($brand->logo, ENT_QUOTES, 'UTF-8') . '" '
                    . 'alt="' . $org . '" style="max-height:48px;max-width:200px;vertical-align:middle;" /> ';
        }

        $html .= '<span style="color:#ffffff;font-weight:700;font-size:1.1em;vertical-align:middle;">' . $org . '</span>'
                . '</td></tr>';

        // ── Content area ──
        $html .= '<tr><td style="padding:28px 32px;">'
                . $content
                . '</td></tr>';

        // ── Footer ──
        $html .= '<tr><td style="padding:16px 32px;background:#f8f9fa;border-top:1px solid #eee;">';

        if (!empty($brand->footer)) {
            $html .= '<div style="margin:0 0 10px;font-size:0.85em;color:#555;">' . $brand->footer . '</div>';
        }

        $html .= '<p style="margin:0;font-size:0.8em;color:#999;">From ' . $org . '</p>';

        if ($footerExtra) {
            $html .= $footerExtra;
        }

        if ($unsubscribeUrl) {
            $html .= '<p style="margin:8px 0 0;font-size:0.75em;color:#bbb;">'
                    . '<a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') . '" style="color:#bbb;">'
                    . 'Unsubscribe from emails</a></p>';
        }

        $html .= '</td></tr></table></td></tr></table></body></html>';

        return $html;
    }

    /**
     * Build a styled CTA button using the brand color.
     *
     * @param   string   $url    Button URL
     * @param   string   $label  Button text
     * @param   ?string  $color  Override color (null = use brand color from config)
     *
     * @return  string  HTML for the button
     *
     * @since   5.4.0
     */
    public static function button(string $url, string $label, ?string $color = null): string
    {
        if ($color === null) {
            $color = self::getBranding()->color;
        }

        return '<p style="margin:20px 0;text-align:center;">'
             . '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" '
             . 'style="display:inline-block;padding:12px 28px;background:' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . ';color:#fff;'
             . 'text-decoration:none;border-radius:6px;font-weight:600;font-size:15px;">'
             . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }

    /**
     * Build a stats table row for digest emails.
     *
     * @param   string  $label  Row label
     * @param   string  $value  Row value
     *
     * @return  string  HTML table row
     *
     * @since   5.4.0
     */
    public static function statRow(string $label, string $value): string
    {
        return '<tr>'
             . '<td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;color:#666;font-size:0.9em;">'
             . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
             . '<td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;font-weight:600;font-size:0.9em;">'
             . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td></tr>';
    }

    /**
     * Open a stats table for digest emails.
     *
     * @return  string
     *
     * @since   5.4.0
     */
    public static function statsTableOpen(): string
    {
        return '<table style="border-collapse:collapse;margin:12px 0;width:100%;border-radius:6px;overflow:hidden;border:1px solid #f0f0f0;">';
    }

    /**
     * Close a stats table.
     *
     * @return  string
     *
     * @since   5.4.0
     */
    public static function statsTableClose(): string
    {
        return '</table>';
    }

    /**
     * Parse a comma/semicolon/space-separated email string into validated addresses.
     *
     * @param   string  $raw  Raw input string
     *
     * @return  array<string>  Array of validated email addresses
     *
     * @since   5.4.0
     */
    public static function parseEmailList(string $raw): array
    {
        $parts = preg_split('/[,;\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $valid = [];

        foreach ($parts as $part) {
            $email = filter_var(trim($part), FILTER_VALIDATE_EMAIL);

            if ($email !== false) {
                $valid[] = $email;
            }
        }

        return array_unique($valid);
    }

    /**
     * Send an HTML email using Joomla's mailer.
     *
     * @param   string   $to               Recipient email address
     * @param   string   $subject          Email subject
     * @param   string   $body             HTML body (should be wrapped via wrapLayout)
     * @param   ?string  $recipientName    Recipient display name
     * @param   ?string  $unsubscribeUrl   URL for List-Unsubscribe header
     * @param   array    $customHeaders    Additional headers ['Name' => 'Value']
     *
     * @return  bool  True on success
     *
     * @throws  \Exception
     * @since   5.4.0
     */
    public static function send(
        string $to,
        string $subject,
        string $body,
        ?string $recipientName = null,
        ?string $unsubscribeUrl = null,
        array $customHeaders = []
    ): bool {
        // Prevent header injection in subject
        $subject = str_replace(["\r", "\n"], '', $subject);

        $app    = Factory::getApplication();
        $mailer = $app->getContainer()->get(MailerFactoryInterface::class)->createMailer();

        $mailer->addRecipient($to, $recipientName);
        $mailer->setSubject($subject);
        $mailer->setBody($body);
        $mailer->isHtml(true);

        if ($unsubscribeUrl) {
            $mailer->addCustomHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>');
            $mailer->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        foreach ($customHeaders as $name => $value) {
            $mailer->addCustomHeader($name, $value);
        }

        $mailer->send();

        return true;
    }
}

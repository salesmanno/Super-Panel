<?php
/**
 * EmailTemplate - Profesjonell HTML e-postmal
 * Dual branding: Salesman.no + firmaets navn
 */
class EmailTemplate {

    public static function wrap($bodyHtml, $companyName = '', $companyLogoUrl = '', $headerBgColor = '#ffffff') {
        $year = date('Y');
        $safeCompanyName = htmlspecialchars($companyName ?: 'Firmaet');
        $safeHeaderBg = preg_match('/^#[0-9a-fA-F]{3,6}$/', $headerBgColor) ? $headerBgColor : '#ffffff';
        $headerTextColor = ($safeHeaderBg === '#ffffff') ? '#6c757d' : '#ffffff';

        $logoHtml = '';
        if ($companyLogoUrl) {
            $safeLogoUrl = htmlspecialchars($companyLogoUrl);
            $logoHtml = "<img src=\"{$safeLogoUrl}\" alt=\"{$safeCompanyName}\" style=\"max-height:50px;max-width:180px;object-fit:contain;\">";
        } else {
            $logoHtml = "<span style=\"font-size:22px;font-weight:700;color:{$headerTextColor};\">{$safeCompanyName}</span>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$safeCompanyName}</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f6f9;">
  <tr>
    <td align="center" style="padding:24px 12px;">
      <table width="620" cellpadding="0" cellspacing="0" border="0" style="max-width:620px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08);">

        <!-- TOP BANNER: Salesman -->
        <tr>
          <td style="background:linear-gradient(135deg,#0d6efd 0%,#0a58ca 100%);padding:10px 32px;text-align:center;">
            <span style="color:#ffffff;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;font-weight:600;opacity:0.9;">Drevet av Salesman.no</span>
          </td>
        </tr>

        <!-- COMPANY HEADER -->
        <tr>
          <td style="background:{$safeHeaderBg};padding:28px 32px 20px;border-bottom:1px solid #e9ecef;">
            <table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td>{$logoHtml}</td>
                <td style="text-align:right;vertical-align:middle;">
                  <span style="font-size:13px;color:{$headerTextColor};">{$safeCompanyName}</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- BODY -->
        <tr>
          <td style="padding:32px;color:#212529;font-size:15px;line-height:1.7;">
            {$bodyHtml}
          </td>
        </tr>

        <!-- DIVIDER -->
        <tr>
          <td style="padding:0 32px;">
            <hr style="border:none;border-top:1px solid #e9ecef;margin:0;">
          </td>
        </tr>

        <!-- FOOTER -->
        <tr>
          <td style="padding:20px 32px;background:#f8f9fa;text-align:center;">
            <p style="margin:0 0 6px;font-size:12px;color:#6c757d;">
              Denne e-posten er sendt av <strong>{$safeCompanyName}</strong> via
              <a href="https://salesman.no" style="color:#0d6efd;text-decoration:none;">Salesman.no</a>
            </p>
            <p style="margin:0;font-size:11px;color:#adb5bd;">
              &copy; {$year} Salesman.no &mdash; Alle rettigheter forbeholdt
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;
    }
}

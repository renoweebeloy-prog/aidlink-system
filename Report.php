<?php
require_once __DIR__ . '/Database.php';

class Report
{
    public static function exportXml(?int $userId = null, ?string $viewerRole = null): string
    {
        $requests = self::requestRows($userId, $viewerRole);

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $root = $xml->createElement('aidRequests');
        $xml->appendChild($root);

        foreach ($requests as $request) {
            $item = $xml->createElement('request');

            foreach ($request as $key => $value) {
                $child = $xml->createElement($key);
                $child->appendChild($xml->createTextNode((string) $value));
                $item->appendChild($child);
            }

            $root->appendChild($item);
        }

        $xmlPath = __DIR__ . '/../public/exports/requests.xml';
        $viewPath = __DIR__ . '/../public/exports/xml_report.html';
        $xml->save($xmlPath);

        $escapedXml = htmlspecialchars($xml->saveXML(), ENT_QUOTES, 'UTF-8');
        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>XML Aid Records</title>'
            . '<style>body{font-family:Arial,sans-serif;background:#f4f8fb;color:#102033;padding:34px}.wrap{max-width:1100px;margin:auto;background:white;border:1px solid #dce6ef;border-radius:24px;padding:28px;box-shadow:0 20px 60px rgba(16,32,51,.10)}pre{white-space:pre-wrap;background:#f8fbff;border:1px solid #dce6ef;border-radius:18px;padding:20px;line-height:1.5}.meta{color:#607089}</style>'
            . '</head><body><div class="wrap"><h1>XML Aid Records</h1><p class="meta">Structured XML file saved at <strong>public/exports/requests.xml</strong>.</p><pre>'
            . $escapedXml . '</pre></div></body></html>';
        file_put_contents($viewPath, $html);

        return 'exports/xml_report.html';
    }

    public static function transformToHtml(?int $userId = null, ?string $viewerRole = null): string
    {
        self::exportXml($userId, $viewerRole);
        $requests = self::requestRows($userId, $viewerRole);
        $htmlPath = __DIR__ . '/../public/exports/report.html';
        $rows = '';

        foreach ($requests as $request) {
            $rows .= '<tr>'
                . '<td>' . htmlspecialchars($request['fullname']) . '</td>'
                . '<td>' . htmlspecialchars($request['category']) . '</td>'
                . '<td>' . htmlspecialchars($request['quantity'] ?? 'Not specified') . '</td>'
                . '<td>' . htmlspecialchars($request['urgency'] ?? 'Medium') . '</td>'
                . '<td>' . htmlspecialchars($request['location']) . '</td>'
                . '<td>' . htmlspecialchars($request['description']) . '</td>'
                . '<td>' . htmlspecialchars($request['status']) . '</td>'
                . '<td>' . htmlspecialchars($request['created_at']) . '</td>'
                . '</tr>';
        }

        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>Aid Request Report</title>'
            . '<style>body{font-family:Arial,sans-serif;background:#f4f8fb;padding:34px;color:#102033}.wrap{max-width:1200px;margin:auto;background:#fff;border:1px solid #dce6ef;border-radius:24px;padding:28px;box-shadow:0 20px 60px rgba(16,32,51,.10)}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{padding:14px;border-bottom:1px solid #dce6ef;text-align:left;vertical-align:top}th{font-size:12px;letter-spacing:1.3px;text-transform:uppercase;color:#607089}.meta{color:#607089}.status{display:inline-block;border:1px solid #dce6ef;border-radius:999px;padding:6px 10px}</style>'
            . '</head><body><div class="wrap"><h1>Aid Request Report</h1><p class="meta">Browser-ready report saved at <strong>public/exports/report.html</strong>.</p><table><thead><tr><th>Recipient</th><th>Category</th><th>Need</th><th>Urgency</th><th>Location</th><th>Description</th><th>Status</th><th>Date</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></body></html>';

        file_put_contents($htmlPath, $html);
        return 'exports/report.html';
    }

    private static function requestRows(?int $userId = null, ?string $viewerRole = null): array
    {
        try { Database::connect()->exec('ALTER TABLE service_requests ADD COLUMN recipient_deleted_at DATETIME NULL'); } catch (Throwable $exception) {}
        try { Database::connect()->exec('ALTER TABLE service_requests ADD COLUMN admin_deleted_at DATETIME NULL'); } catch (Throwable $exception) {}
        try { Database::connect()->exec('ALTER TABLE service_requests ADD COLUMN staff_deleted_at DATETIME NULL'); } catch (Throwable $exception) {}

        $sql = 'SELECT sr.*, u.fullname FROM service_requests sr JOIN users u ON u.id = sr.user_id';

        if ($userId) {
            $statement = Database::connect()->prepare($sql . ' WHERE sr.user_id = ? AND sr.recipient_deleted_at IS NULL ORDER BY sr.created_at DESC');
            $statement->execute([$userId]);
            return $statement->fetchAll();
        }

        $deleteColumn = $viewerRole === 'admin' ? 'admin_deleted_at' : 'staff_deleted_at';
        return Database::connect()->query($sql . ' WHERE sr.' . $deleteColumn . ' IS NULL ORDER BY sr.created_at DESC')->fetchAll();
    }
}

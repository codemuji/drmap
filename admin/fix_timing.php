<?php
require_once __DIR__ . '/inc/auth.php';
require_admin_login();
require_once __DIR__ . '/inc/db.php';

$pdo = getPDO();

// Helper to attempt to normalize timing value into a PHP array/object
function normalize_timing($raw) {
    $raw = trim($raw ?? '');
    if ($raw === '') return null;

    // 1) If already valid JSON => return decoded
    $dec = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($dec) || is_object($dec))) {
        return $dec;
    }

    // 2) Try stripslashes
    $s2 = stripslashes($raw);
    $dec = json_decode($s2, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($dec) || is_object($dec))) {
        return $dec;
    }

    // 3) If double-encoded JSON (string inside JSON)
    $step = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_string($step)) {
        $dec = json_decode($step, true);
        if (json_last_error() === JSON_ERROR_NONE && (is_array($dec) || is_object($dec))) return $dec;
    }

    // 4) Best-effort: try to convert single quotes to double quotes and quote keys
    $p3 = $raw;
    // normalize smart quotes
    $p3 = str_replace(["\xE2\x80\x98", "\xE2\x80\x99", "\xE2\x80\x9C", "\xE2\x80\x9D"], ["'","'","\"","\""], $p3);
    // replace single-quoted keys like 'key': with "key":
    $p3 = preg_replace("/([\{,\s])'([^']+?)'\s*:/u", '$1"$2":', $p3);
    // replace single-quoted values
    $p3 = preg_replace("/:\s*'([^']*?)'(\s*[,\}])/u", ': "$1"$2', $p3);
    // remove trailing commas
    $p3 = preg_replace('/,\s*([}\]])/', '$1', $p3);

    $dec = json_decode($p3, true);
    if (json_last_error() === JSON_ERROR_NONE && (is_array($dec) || is_object($dec))) return $dec;

    // 5) As last resort, try to extract day->open/close pairs via regex
    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $recovered = [];
    foreach ($days as $d) {
        if (preg_match('/"?' . $d . '"?\s*:\s*\{([^}]*)\}/i', $raw, $m)) {
            $body = $m[1];
            if (preg_match('/open"?\s*:\s*"?([0-9:\- ]{3,8})"?/i', $body, $o)) {
                $open = trim($o[1]);
                $close = '';
                if (preg_match('/close"?\s*:\s*"?([0-9:\- ]{3,8})"?/i', $body, $c)) $close = trim($c[1]);
                $recovered[$d] = ['enabled' => true, 'slots' => [['open' => $open, 'close' => $close]]];
            }
        }
    }
    if (!empty($recovered)) return $recovered;

    // cannot recover
    return null;
}

// Run normalization across all doctors (or single if id param given)
$idFilter = isset($_GET['id']) ? (int)$_GET['id'] : null;

$query = 'SELECT id, timing FROM doctors' . ($idFilter ? ' WHERE id = :id' : '');
$stmt = $pdo->prepare($query);
if ($idFilter) $stmt->execute([$idFilter]); else $stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = ['total' => count($rows), 'updated' => 0, 'skipped' => 0, 'errors' => []];
foreach ($rows as $r) {
    $id = (int)$r['id'];
    $raw = $r['timing'];
    $norm = normalize_timing($raw);
    if ($norm === null) {
        $results['skipped']++;
        continue;
    }
    // encode normalized structure
    $json = json_encode($norm);
    if ($json === false) {
        $results['errors'][] = "encode_failed:$id";
        continue;
    }
    try {
        $u = $pdo->prepare('UPDATE doctors SET timing = :timing WHERE id = :id');
        $u->execute(['timing' => $json, 'id' => $id]);
        $results['updated']++;
    } catch (Exception $e) {
        $results['errors'][] = "update_error:$id:" . $e->getMessage();
    }
}

?><!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fix Timing Data</title>
    <style>body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:18px;} pre{background:#f8fafc;border:1px solid #e2e8f0;padding:12px;border-radius:8px;}</style>
</head>
<body>
    <h1>Timing Data Fixer</h1>
    <p>Total rows processed: <strong><?php echo htmlspecialchars($results['total']); ?></strong></p>
    <p>Updated: <strong><?php echo htmlspecialchars($results['updated']); ?></strong></p>
    <p>Skipped (could not recover): <strong><?php echo htmlspecialchars($results['skipped']); ?></strong></p>
    <?php if (!empty($results['errors'])): ?>
    <h3>Errors</h3>
    <pre><?php echo htmlspecialchars(implode("\n", $results['errors'])); ?></pre>
    <?php endif; ?>

    <p><a href="../doctors.php">Back to list</a></p>
</body>
</html>

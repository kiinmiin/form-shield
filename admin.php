<?php
// form-shield/admin.php
$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');

// Uue malli lisamine
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_template'])) {
    $stmt = $db->query("SELECT value FROM config WHERE key = 'known_templates'");
    $val = $stmt->fetchColumn();
    $templates = $val ? json_decode($val, true) : [];
    $templates[] = $_POST['new_template'];
    
    $stmt = $db->prepare("INSERT OR REPLACE INTO config (key, value) VALUES ('known_templates', :value)");
    $stmt->execute([':value' => json_encode(array_values(array_unique($templates)))]);
}

// Mallide lugemine
$stmt = $db->query("SELECT value FROM config WHERE key = 'known_templates'");
$val = $stmt->fetchColumn();
$templates = $val ? json_decode($val, true) : ['seo optimization', 'crypto trading', 'casino'];

// Logide lugemine
$logs = $db->query("SELECT * FROM spam_logs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>FormShield Admin</title></head>
<body>
    <h1>FormShield Admin Paneel</h1>
    
    <h2>Lisa keelatud tekst / šabloon</h2>
    <form method="POST">
        <input type="text" name="new_template" placeholder="nt. 'SEO agentuur'" required>
        <button type="submit">Lisa</button>
    </form>

    <h3>Aktiivsed šabloonid:</h3>
    <ul>
        <?php foreach($templates as $t): ?> <li><?php echo htmlspecialchars($t); ?></li> <?php endforeach; ?>
    </ul>

    <h2>Spämmi Logi</h2>
    <table border="1">
        <tr><th>Aeg</th><th>IP</th><th>Põhjus</th><th>Andmed</th></tr>
        <?php foreach($logs as $log): ?>
        <tr>
            <td><?php echo $log['created_at']; ?></td>
            <td><?php echo $log['ip']; ?></td>
            <td><?php echo $log['reason']; ?></td>
            <td><code><?php echo htmlspecialchars($log['payload']); ?></code></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

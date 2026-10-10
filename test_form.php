<?php

require_once __DIR__ . '/FormShield.php';

$message_result = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shield = new FormShield();
    $result = $shield->check($_POST, $_SERVER['REMOTE_ADDR']);

    if ($result['status'] === 'spam') {
        if (isset($result['http_hint']) && $result['http_hint'] == 429) {
            http_response_code(429);
            die("Viga 429: Liiga palju päringuid ühelt IP-aadressilt. Päring blokeeritud.");
        }
        
        if ($result['silent_ok']) {
            $message_result = "Sõnum edukalt saadetud! (Taustal rakendus tegelikult blokeeris selle põhjusega: " . $result['reason'] . ")";
        } else {
            $message_result = "Viga: Spämmituvastus (" . $result['reason'] . ")";
        }
    } else {
        $message_result = "Kiri on legitiimne! Saadetakse postkasti.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Test Vorm</title></head>
<body>
    <h1>Kontaktivorm (FormShield test)</h1>
    <?php if($message_result) echo "<p><strong>$message_result</strong></p>"; ?>

    <form method="POST">
        <div style="display:none;">
            <input type="text" name="website_real" value="">
        </div>

        Nimi:<br> <input type="text" name="name"><br><br>
        Telefon:<br> <input type="text" name="phone" placeholder="nt. +372 512 3456"><br><br>
        Sõnum:<br> <textarea name="message"></textarea><br><br>
        
        <button type="submit">Saada päring</button>
    </form>
</body>
</html>

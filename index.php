<?php
if (empty($_GET['name']) || empty($_GET['tag'])) {
    die("Bitte gib ?name=DeinName&tag=DeinTag in der URL an.");
}

$name = urlencode($_GET['name']);
$tag = urlencode($_GET['tag']);
// HIER MUSS IHR PERSÖNLICHER RIOT API KEY EINGEFÜGT WERDEN
$apiKey = "DEIN_RIOTS_API_KEY_HIER_EINFUEGEN"; 

function callRiotApi(string $url, string $apiKey): string {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Riot-Token: $apiKey"
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        die("Fehler bei API-Anfrage. HTTP-Code: $httpCode");
    }
    return $response;
}

// PUUID holen
$puuidUrl = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/$name/$tag";
$puuidResponse = callRiotApi($puuidUrl, $apiKey);
$puuidData = json_decode($puuidResponse, true);
$puuid = $puuidData['puuid'] ?? null;

if (!$puuid) {
    die("Konnte den Spieler nicht finden.");
}

// Rangdaten holen (array)
$rankUrl = "https://euw1.api.riotgames.com/tft/league/v1/by-puuid/$puuid";
$rankResponse = callRiotApi($rankUrl, $apiKey);
$rankData = json_decode($rankResponse, true);

if (!$rankData || !is_array($rankData) || count($rankData) === 0) {
    die("Keine TFT-Rangdaten gefunden.");
}

// Wir nehmen den ersten Eintrag mit queueType = RANKED_TFT
$tftData = null;
foreach ($rankData as $entry) {
    if ($entry['queueType'] === 'RANKED_TFT') {
        $tftData = $entry;
        break;
    }
}

if (!$tftData) {
    die("Keine TFT-Rangdaten für RANKED_TFT gefunden.");
}

$tier = ucfirst(strtolower($tftData['tier']));
$rank = $tftData['rank'];
$lp = $tftData['leaguePoints'];

echo "$tier $rank - $lp LP";

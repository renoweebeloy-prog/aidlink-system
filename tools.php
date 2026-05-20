<?php
session_start();
require_once __DIR__ . '/app/helpers.php';
require_login();

$regexResult = '';
$weather = null;
$weatherError = '';
$locations = [
    'Mati City, Davao Oriental',
    'Davao City, Davao del Sur',
    'Digos City, Davao del Sur',
    'Tagum City, Davao del Norte',
    'Panabo City, Davao del Norte',
    'Samal, Davao del Norte',
    'Lupon, Davao Oriental',
    'Baganga, Davao Oriental',
    'Cateel, Davao Oriental',
    'Boston, Davao Oriental',
    'Manay, Davao Oriental',
    'Banaybanay, Davao Oriental',
    'San Isidro, Davao Oriental',
    'Caraga, Davao Oriental',
    'Governor Generoso, Davao Oriental',
    'Compostela, Davao de Oro',
    'Nabunturan, Davao de Oro',
    'Monkayo, Davao de Oro',
];

if (isset($_POST['regex_text'])) {
    $text = $_POST['regex_text'];
    $pattern = '/\b[A-Z][a-z]+(?:\s[A-Z][a-z]+)*\b/';
    preg_match_all($pattern, $text, $matches);
    $regexResult = implode(', ', array_unique($matches[0])) ?: 'No matching names found.';
}

if (isset($_POST['weather'])) {
    $city = trim($_POST['city'] ?? '') ?: 'Mati City, Davao Oriental, Philippines';
    $lat = trim($_POST['weather_lat'] ?? '');
    $lon = trim($_POST['weather_lon'] ?? '');
    $query = $city;

    if ($lat !== '' && $lon !== '' && is_numeric($lat) && is_numeric($lon)) {
        $query = $lat . ',' . $lon;
    }

    $apiUrl = 'https://wttr.in/' . urlencode($query) . '?format=j1';
    $json = @file_get_contents($apiUrl);
    $weather = $json ? json_decode($json, true) : null;

    if (!$weather) {
        $weatherError = 'Weather details are temporarily unavailable.';
    }
}

$current = $weather['current_condition'][0] ?? null;
$area = trim($_POST['city'] ?? '') ?: ($weather['nearest_area'][0]['areaName'][0]['value'] ?? 'Mati City');
$temp = $current['temp_C'] ?? '--';
$desc = $current['weatherDesc'][0]['value'] ?? 'Local conditions';
$humidity = $current['humidity'] ?? '--';
$wind = $current['windspeedKmph'] ?? '--';
$feels = $current['FeelsLikeC'] ?? '--';
$uv = $current['uvIndex'] ?? '--';
$forecast = $weather['weather'][0] ?? [];
$maxTemp = $forecast['maxtempC'] ?? '--';
$minTemp = $forecast['mintempC'] ?? '--';
$descLower = strtolower($desc);
$weatherIcon = '☁️';

if (str_contains($descLower, 'sun') || str_contains($descLower, 'clear')) {
    $weatherIcon = '☀️';
} elseif (str_contains($descLower, 'rain') || str_contains($descLower, 'shower')) {
    $weatherIcon = '🌧️';
} elseif (str_contains($descLower, 'thunder')) {
    $weatherIcon = '⛈️';
} elseif (str_contains($descLower, 'partly')) {
    $weatherIcon = '⛅';
}

ob_start();
?>
<section class="page-head reveal">
    <div>
        <span class="eyebrow">Tools</span>
        <h1>Field Utilities</h1>
        <p class="lead">Quick references and support tools for donation planning, volunteer coordination, and local aid operations.</p>
    </div>
</section>

<datalist id="locationSuggestions">
    <?php foreach ($locations as $location): ?>
        <option value="<?= e($location) ?>">
    <?php endforeach; ?>
</datalist>

<section class="tool-grid refined">
    <div class="tool-stack">
        <article class="panel reveal">
            <h2>Regex Name Scanner</h2>
            <form method="POST">
                <textarea name="regex_text" rows="5" placeholder="Paste names or notes here..."></textarea>
                <button class="button" type="submit">Scan Text</button>
            </form>
            <?php if ($regexResult): ?>
                <p class="success-box"><?= e($regexResult) ?></p>
            <?php endif; ?>
        </article>

        <article class="panel reveal">
            <h2>Local Scripts</h2>
            <p>Run maintenance helpers when preparing backups, aid summaries, and coordination records.</p>
            <pre>powershell -ExecutionPolicy Bypass -File scripts/backup.ps1</pre>
            <pre>scripts\system_report.bat</pre>
        </article>
    </div>

    <article class="weather-panel reveal">
        <form class="weather-search" method="POST">
            <input type="text" name="city" data-location-suggest data-lat-field="weather_lat" data-lon-field="weather_lon" placeholder="Search city, municipality, or country" value="<?= e($_POST['city'] ?? '') ?>">
            <input type="hidden" name="weather_lat" value="<?= e($_POST['weather_lat'] ?? '') ?>">
            <input type="hidden" name="weather_lon" value="<?= e($_POST['weather_lon'] ?? '') ?>">
            <button class="small-button" name="weather" aria-label="Check weather">Check</button>
        </form>

        <div class="weather-card">
            <div class="weather-location">
                <span>⌖</span>
                <?= e($area) ?>
            </div>
            <div class="weather-main">
                <div>
                    <p>Weather Now</p>
                    <strong><?= e($temp) ?>°C</strong>
                    <span>Feels like <?= e($feels) ?>°</span>
                </div>
                <div class="weather-icon" aria-hidden="true"><?= $weatherIcon ?></div>
            </div>
            <p class="weather-desc"><?= e($desc) ?></p>
            <div class="weather-range">
                <span>High: <?= e($maxTemp) ?>°</span>
                <span>Low: <?= e($minTemp) ?>°</span>
            </div>
        </div>

        <div class="weather-highlights">
            <div><span>Humidity</span><strong><?= e($humidity) ?>%</strong></div>
            <div><span>Wind</span><strong><?= e($wind) ?> km/h</strong></div>
            <div><span>UV Index</span><strong><?= e($uv) ?></strong></div>
        </div>

        <?php if ($weatherError): ?>
            <p class="error-box"><?= e($weatherError) ?></p>
        <?php endif; ?>
    </article>
</section>
<?php
$content = ob_get_clean();
$title = 'Tools - AidLink';
require __DIR__ . '/layout.php';

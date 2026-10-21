<?php
/**
 * SpellBinder Archivist Pro
 * Optimized for Gregory's MAMP Installation
 */

set_time_limit(0);
ini_set('memory_limit', '512M');

header('Content-Type: text/html; charset=utf-8');
echo "<style>
        body { background: #030712; color: #d1d5db; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .log { background: #111827; border: 1px solid #1f2937; padding: 20px; border-radius: 12px; font-family: monospace; max-height: 400px; overflow: auto; }
        .success { color: #10b981; }
      </style>";

echo "<h1>Updating the Grimoire, Gregory...</h1><div class='log' id='log'>";
ob_flush(); flush();

$baseUrl = "https://www.dnd5eapi.co/api/spells";
$schoolMaps = ["Abjuration" => "Abjur.", "Conjuration" => "Conjur.", "Divination" => "Divin.", "Enchantment" => "Enchan.", "Evocation" => "Evoc.", "Illusion" => "Illus.", "Necromancy" => "Necro.", "Transmutation" => "Trans."];

function parseTactical($data) {
    if (isset($data['damage']['damage_at_slot_level'])) return [reset($data['damage']['damage_at_slot_level']) . " " . ($data['damage']['damage_type']['name'] ?? ''), 'dmg-atk'];
    if (isset($data['damage']['damage_at_character_level'])) return [reset($data['damage']['damage_at_character_level']) . " " . ($data['damage']['damage_type']['name'] ?? ''), 'dmg-atk'];
    if (isset($data['heal_at_slot_level'])) return [reset($data['heal_at_slot_level']) . "+Mod HP", 'dmg-heal'];
    
    $desc = implode(" ", $data['desc']);
    if (preg_match('/([+-]\d+)\s+bonus\s+to\s+AC/i', $desc, $m)) return [$m[1] . " AC Buff", 'dmg-heal'];
    if (preg_match('/hit\s+point\s+maximum\s+.*increase\s+by\s+(\d+)/i', $desc, $m)) return ["+" . $m[1] . " Max HP", 'dmg-heal'];
    
    return ["Utility", "dmg-neutral"];
}

$indexData = json_decode(file_get_contents($baseUrl), true);
$grimoire = [];

foreach ($indexData['results'] as $spell) {
    $data = json_decode(file_get_contents("https://www.dnd5eapi.co" . $spell['url']), true);
    if (!$data) continue;

    $tactical = parseTactical($data);
    $grimoire[] = [
        "id" => $data['index'],
        "name" => $data['name'],
        "level" => $data['level'],
        "school" => $schoolMaps[$data['school']['name']] ?? $data['school']['name'],
        "time" => str_ireplace(['1 action', '1 bonus action', '1 reaction'], ['Action', 'Bonus Action', 'Reaction'], $data['casting_time']),
        "range" => $data['range'],
        "comp" => implode(", ", $data['components']),
        "dur" => str_replace(['ms', 'hs', 'rs'], ['m', 'h', 'r'], str_ireplace(['Instantaneous', ' minute', ' hour', ' round'], ['Inst.', 'm', 'h', 'r'], $data['duration'] ?? 'Inst.')),
        "conc" => (bool)$data['concentration'],
        "classes" => array_column($data['classes'], 'name'),
        "source" => "https://dnd5e.wikidot.com/spell:" . $data['index'],
        "desc" => $data['desc'][0] ?? "",
        "damage" => $tactical[0],
        "damage-class" => $tactical[1]
    ];
    echo "Archived: <span class='success'>" . htmlspecialchars($data['name']) . "</span><br>";
    echo "<script>document.getElementById('log').scrollTop = 9999;</script>";
    ob_flush(); flush();
}

file_put_contents('spells.json', json_encode($grimoire, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "</div><h2 class='success'>Synchronization Complete.</h2>";
?>
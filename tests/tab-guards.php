<?php
/**
 * Test autonome (sans GLPI ni plugin pdf) : les méthodes d'onglet ne doivent
 * ni lever d'exception ni afficher quoi que ce soit pour un item qui n'est pas
 * un CommonDBTM (ex. PluginPdfTicket) ou qui n'est pas encore enregistré.
 *
 * Usage : php tests/tab-guards.php
 */

declare(strict_types=1);

// ── Doublures minimales de GLPI ──────────────────────────────────────────────
class CommonGLPI
{
    public function getType(): string
    {
        return static::class;
    }

    public static function createTabEntry($text, $nb = 0, $form = null, $icon = '')
    {
        return $text . ' ' . $nb;
    }
}

class CommonDBTM extends CommonGLPI
{
    public $fields = [];

    public function getID(): int
    {
        return (int) ($this->fields['id'] ?? -1);
    }

    public function isNewItem(): bool
    {
        return $this->getID() <= 0;
    }
}

function __($s, $d = '')
{
    return $s;
}

function _n($s, $p, $n, $d = '')
{
    return $s;
}

function countElementsInTable($table, $where = [])
{
    return 0;
}

require $argv[1] ?? __DIR__ . '/../plugin/checklist/inc/checklist.class.php';

// ── Cas de test ──────────────────────────────────────────────────────────────
/** Équivalent de PluginPdfTicket : un CommonGLPI sans getID() */
class FakePdfTab extends CommonGLPI
{
}

$failures = 0;
$check = function (string $name, bool $ok) use (&$failures): void {
    echo ($ok ? 'ok   ' : 'FAIL ') . $name . PHP_EOL;
    $failures += $ok ? 0 : 1;
};

$tab = new PluginChecklistChecklist();
$notDbtm = new FakePdfTab();
$newItem = new CommonDBTM();
$saved = new CommonDBTM();
$saved->fields['id'] = 42;

foreach (['non-CommonDBTM item' => $notDbtm, 'unsaved CommonDBTM item' => $newItem] as $label => $item) {
    try {
        $name = $tab->getTabNameForItem($item);
        $check("getTabNameForItem returns '' for $label", $name === '');

        ob_start();
        $shown = PluginChecklistChecklist::displayTabContentForItem($item);
        $output = ob_get_clean();
        $check("displayTabContentForItem returns false and prints nothing for $label", $shown === false && $output === '');
    } catch (Throwable $e) {
        $check("no exception for $label (" . $e->getMessage() . ')', false);
    }
}

// Un item enregistré garde son onglet
$check('saved CommonDBTM item still gets a tab', $tab->getTabNameForItem($saved) !== '');

exit($failures === 0 ? 0 : 1);

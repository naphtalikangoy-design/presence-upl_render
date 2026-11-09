<?php
namespace App\Services\Wifi;

use App\Models\{JournalAudit, PointAcces};
use Illuminate\Http\Request;

/** Modification réelle de la puissance : exécutée par le matériel via le connecteur, ou refusée. Jamais simulée. */
class TxPowerService
{
    public function __construct(private ConnectorManager $connectors) {}

    public function apply(PointAcces $ap, array $settings, Request $request): array
    {
        $c = $this->connectors->driver();
        if ($c->key() === 'demo') throw new UnsupportedOperationException('Mode démo : aucune modification matérielle possible.');
        if (!($c->capabilities()['tx_power'] ?? false)) throw new UnsupportedOperationException('Le connecteur actif ne permet pas de modifier la puissance.');
        $caps = $ap->capacites ?? [];
        if (!($caps['tx_power'] ?? false) || !isset($caps['tx_min'], $caps['tx_max']))
            throw new UnsupportedOperationException('Cet Access Point n\'est pas déclaré compatible (capacites.tx_power, tx_min, tx_max).');
        $dbm = (int) ($settings['puissance_dbm'] ?? -999);
        if ($dbm < $caps['tx_min'] || $dbm > $caps['tx_max']) throw new \InvalidArgumentException("Puissance hors limites ({$caps['tx_min']}–{$caps['tx_max']} dBm).");

        $res = $c->setTxPower($ap, $settings);
        JournalAudit::noter('reseau.puissance_modification', $request, $ap, ['demande' => $settings, 'confirme_par_materiel' => $res['confirmed']]);
        if ($res['confirmed']) $ap->update(['puissance_dbm' => $dbm]);
        return $res;
    }
}

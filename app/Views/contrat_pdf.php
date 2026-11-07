<?php
$c = $contrat;
$ouiNon = function ($v) { return $v ? 'Oui' : 'Non'; };
$fmtDate = function ($d) { return $d ? date('d/m/Y', strtotime($d)) : '—'; };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    h1 { text-align: center; color: #b8791a; font-size: 22px; margin-bottom: 4px; }
    .subtitle { text-align: center; color: #888; font-size: 11px; margin-bottom: 30px; }
    .section-title { background: #fdf0d5; color: #b8791a; padding: 8px 12px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 6px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
    td.label { width: 40%; color: #666; font-weight: bold; }
    .footer { margin-top: 40px; font-size: 10px; color: #999; text-align: center; }
    .signature { margin-top: 40px; }
    .signature td { width: 50%; border: none; padding-top: 60px; text-align: center; }
</style>
</head>
<body>

<h1>CONTRAT DE TRAVAIL</h1>
<div class="subtitle">Référence : CTR-<?= str_pad($c['id'], 5, '0', STR_PAD_LEFT) ?> — Établi le <?= date('d/m/Y') ?></div>

<div class="section-title">1. Parties concernées</div>
<table>
    <tr><td class="label">Employeur</td><td>Société RH Recrutement</td></tr>
    <tr><td class="label">Salarié(e)</td><td><?= esc($c['nom']) ?></td></tr>
    <tr><td class="label">Email</td><td><?= esc($c['email']) ?></td></tr>
    <tr><td class="label">Poste occupé</td><td><?= esc($c['poste']) ?></td></tr>
</table>

<div class="section-title">2. Caractéristiques du contrat</div>
<table>
<tr>
    <td class="label">Type de contrat</td>
    <td>
        <?php
            $types = [
                'Essai' => 'Contrat d\'essai',
                'CDD'   => 'Contrat à durée déterminée (CDD)',
                'CDI'   => 'Contrat à durée indéterminée (CDI)',
            ];
            echo esc($types[$c['type_contrat']] ?? $c['type_contrat']);
        ?>
    </td>
</tr>    <tr><td class="label">Date de début</td><td><?= $fmtDate($c['date_debut']) ?></td></tr>
<tr>
    <td class="label">Date de fin</td>
    <td>
        <?php if ($c['type_contrat'] === 'CDI'): ?>
            Durée indéterminée
        <?php else: ?>
            <?= $fmtDate($c['date_fin']) ?>
        <?php endif; ?>
    </td>
</tr>    <tr><td class="label">Heures de travail / semaine</td><td><?= (int) $c['heures_travail'] ?> h</td></tr>
</table>

<div class="section-title">3. Rémunération</div>
<table>
    <tr><td class="label">Salaire proposé</td><td><?= number_format($c['salaire_propose'], 2) ?></td></tr>
    <tr><td class="label">Salaire négocié (retenu)</td><td><strong><?= number_format($c['salaire_negocie'], 2) ?></strong></td></tr>
</table>

<div class="section-title">4. Conditions particulières</div>
<table>
    <tr><td class="label">Télétravail autorisé</td><td><?= $ouiNon($c['teletravail']) ?></td></tr>
    <tr><td class="label">Période d'essai</td><td><?= $ouiNon($c['periode_essai']) ?></td></tr>
    <tr><td class="label">Heures supplémentaires</td><td><?= $ouiNon($c['heures_sup']) ?></td></tr>
    <tr><td class="label">Mutuelle santé</td><td><?= $ouiNon($c['mutuelle']) ?></td></tr>
    <tr><td class="label">Avantages</td><td><?= nl2br(esc($c['avantages'] ?: '—')) ?></td></tr>
</table>

<table class="signature">
    <tr>
        <td>
            <strong>Signature de l'employeur</strong><br><br>
            _______________________
        </td>
        <td>
            <strong>Signature du salarié</strong><br><br>
            _______________________
        </td>
    </tr>
</table>

<div class="footer">
    Document généré automatiquement par le système de recrutement — <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>
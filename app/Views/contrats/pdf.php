<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Contrat #<?= (int) $contrat['id'] ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; line-height: 1.6; }
        h1 { text-align: center; font-size: 20px; margin-bottom: 5px; }
        .muted { color: #666; text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f5f5f5; width: 35%; }
        .signature { margin-top: 60px; }
        .signature td { border: none; padding-top: 50px; }
    </style>
</head>
<body>
    <h1>CONTRAT DE TRAVAIL</h1>
    <p class="muted">TalentFlow — <?= esc($contrat['departement'] ?? '') ?></p>

    <p>
        Entre l’entreprise <strong>TalentFlow</strong>, représentée par sa Direction des Ressources Humaines,
        et <strong><?= esc(trim(($contrat['prenom'] ?? '') . ' ' . ($contrat['nom'] ?? ''))) ?></strong>,
        matricule <strong><?= esc($contrat['matricule'] ?? '—') ?></strong>,
        il a été convenu ce qui suit.
    </p>

    <table>
        <tr><th>Type de contrat</th><td><?= esc($contrat['type_contrat']) ?></td></tr>
        <tr><th>Poste</th><td><?= esc($contrat['poste'] ?? '—') ?></td></tr>
        <tr><th>Département</th><td><?= esc($contrat['departement'] ?? '—') ?></td></tr>
        <tr><th>Salaire mensuel brut</th><td><?= tf_money($contrat['salaire_mensuel']) ?></td></tr>
        <tr><th>Date de début</th><td><?= tf_date($contrat['date_debut']) ?></td></tr>
        <tr><th>Date de fin</th><td><?= $contrat['date_fin'] ? tf_date($contrat['date_fin']) : 'Contrat à durée indéterminée' ?></td></tr>
        <tr><th>Période d’essai</th><td><?= (int) $contrat['periode_essai_mois'] ?> mois</td></tr>
        <tr><th>Horaires</th><td><?= substr($contrat['heure_debut'], 0, 5) ?> – <?= substr($contrat['heure_fin'], 0, 5) ?></td></tr>
        <tr><th>Régime de travail</th><td><?= esc($contrat['regime'] ?? '—') ?></td></tr>
    </table>

    <p>
        Le salarié s’engage à respecter le règlement intérieur, les horaires de travail et les règles de
        confidentialité de l’entreprise. Le contrat est régi par la législation du travail en vigueur.
    </p>

    <table class="signature">
        <tr>
            <td>Pour l’entreprise<br><br><br>________________________</td>
            <td>Le salarié<br><br><br>________________________</td>
        </tr>
    </table>

    <p style="margin-top:40px;color:#777;font-size:10px;text-align:center">
        Document généré automatiquement le <?= date('d/m/Y à H:i') ?>.
    </p>
</body>
</html>

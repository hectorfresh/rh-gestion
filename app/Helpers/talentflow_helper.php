<?php

/**
 * Helpers d'affichage TalentFlow.
 */

if (! function_exists('tf_badge')) {
    function tf_badge(?string $statut): string
    {
        $statut = (string) $statut;
        $s = mb_strtolower($statut);

        $classe = 'status-gray';
        if (str_contains($s, 'non retenue') || str_contains($s, 'refus') || str_contains($s, 'absent') || str_contains($s, 'annul') || str_contains($s, 'injustif')) {
            $classe = 'status-red';
        } elseif (str_contains($s, 'accept') || str_contains($s, 'valid') || str_contains($s, 'embauch') || str_contains($s, 'present') || str_contains($s, 'pourvu')) {
            $classe = 'status-green';
        } elseif (str_contains($s, 'attente') || str_contains($s, 'créé') || str_contains($s, 'planifié') || str_contains($s, 'retard')) {
            $classe = 'status-orange';
        } elseif (str_contains($s, 'nouvelle') || str_contains($s, 'reçue') || str_contains($s, 'retenue') || str_contains($s, 'entretien') || str_contains($s, 'prête')) {
            $classe = 'status-blue';
        }

        return '<span class="status-badge ' . $classe . '">' . esc($statut) . '</span>';
    }
}

if (! function_exists('tf_money')) {
    function tf_money($montant, string $suffixe = ' Ar'): string
    {
        return number_format((float) $montant, 0, ',', ' ') . $suffixe;
    }
}

if (! function_exists('tf_date')) {
    function tf_date(?string $date, string $format = 'd/m/Y'): string
    {
        if (! $date) {
            return '—';
        }
        $ts = strtotime($date);
        return $ts ? date($format, $ts) : '—';
    }
}

if (! function_exists('tf_datetime')) {
    function tf_datetime(?string $date): string
    {
        return tf_date($date, 'd/m/Y H:i');
    }
}

if (! function_exists('tf_decision')) {
    function tf_decision(?string $decision): string
    {
        return match ($decision) {
            'accepte'    => '<span class="status-badge status-green">Accepté</span>',
            'refuse'     => '<span class="status-badge status-red">Refusé</span>',
            'en_attente' => '<span class="status-badge status-orange">En attente</span>',
            default      => '<span class="status-badge status-gray">—</span>',
        };
    }
}

if (! function_exists('tf_statut_proposition')) {
    function tf_statut_proposition(?string $statut): string
    {
        $libelle = match ($statut) {
            'en_cours'    => 'En cours',
            'en_attente'  => 'En attente',
            'confirmable' => 'Prête à confirmer',
            'confirmee'   => 'Confirmée',
            'abandonnee'  => 'Abandonnée',
            default       => (string) $statut,
        };

        return tf_badge($libelle);
    }
}

if (! function_exists('tf_statut_element')) {
    function tf_statut_element(?string $statut, ?string $reponse = null): string
    {
        $libelle = match ($statut) {
            'propose'           => $reponse === 'oui' ? 'Accepté' : 'En attente',
            'accepte'           => 'Accepté',
            'contre_proposition'=> 'Contre-proposition en cours',
            'contre_validee'    => 'Contre-proposition acceptée',
            'contre_refusee'    => 'Contre-proposition refusée',
            default             => (string) $statut,
        };

        return tf_badge($libelle);
    }
}

if (! function_exists('tf_role')) {
    function tf_role(?string $role): string
    {
        return match ($role) {
            'dg'      => 'Direction générale',
            'rh'      => 'Ressources humaines',
            'manager' => 'Manager',
            'employe' => 'Employé',
            'candidat'=> 'Candidat',
            default   => (string) $role,
        };
    }
}

if (! function_exists('tf_initiales')) {
    function tf_initiales(?string $nom, ?string $prenom = ''): string
    {
        return mb_strtoupper(mb_substr((string) $prenom, 0, 1) . mb_substr((string) $nom, 0, 1));
    }
}

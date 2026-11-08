#!/bin/bash
# ==============================================================
# SCRIPT DE TEST COMPLET (End-to-End) - SYSTEME DE RECRUTEMENT
# ==============================================================
# Usage : ./test_flow.sh
# Prérequis : Le serveur CI4 doit tourner (ex: php spark serve)
#             CSRF doit être désactivé dans Filters.php
#             Le script utilise 'sudo mysql' pour la BDD
# ==============================================================

set -e

# CONFIGURATION
BASE="http://localhost:8080"
MYSQL="sudo mysql"  # Utilise sudo pour éviter l'erreur "Access denied"
DIR="/tmp/rh_test"

# Nettoyage et création du dossier temporaire
rm -rf "$DIR" && mkdir -p "$DIR"

C_MANAGER="$DIR/manager.txt"
C_RH="$DIR/rh.txt"
C_DG="$DIR/dg.txt"
C_CANDIDAT="$DIR/candidat.txt"

# Couleurs pour le terminal
GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; NC='\033[0m'
PASS=0; FAIL=0

step() { echo -e "\n${YELLOW}▶ $1${NC}"; }
ok()   { echo -e "${GREEN}✅ $1${NC}"; PASS=$((PASS+1)); }
ko()   { echo -e "${RED}❌ $1${NC}"; FAIL=$((FAIL+1)); }

# -------------------------------------------------------------
# FONCTION DE CONNEXION
# -------------------------------------------------------------
login() {
    local email=$1 cookie=$2 label=$3
    curl -s -c "$cookie" -b "$cookie" -L \
        -d "email=$email" -d "mdp=1234" "$BASE/login" > /dev/null
    
    # Vérifie si le login a réussi en cherchant "Déconnexion" sur la page d'accueil
    if curl -s -b "$cookie" "$BASE/accueil" | grep -q "Déconnexion"; then
        ok "Login $label"
    else
        ko "Login $label (Échec de connexion)"
    fi
}

# -------------------------------------------------------------
# DÉBUT DES TESTS
# -------------------------------------------------------------
echo -e "=============================================="
echo -e "   LANCEMENT DU TEST E2E - RECRUTEMENT"
echo -e "=============================================="

step "1. Login des 4 acteurs"
login "manager@mail.com"  "$C_MANAGER"  "Manager"
login "rh@mail.com"       "$C_RH"       "RH"
login "dg@mail.com"       "$C_DG"       "DG"
login "candidat@mail.com" "$C_CANDIDAT" "Candidat"

# -------------------------------------------------------------
step "2. Manager crée un besoin"
# -------------------------------------------------------------
curl -s -b "$C_MANAGER" -c "$C_MANAGER" -L \
    -d "poste=1" -d "nombre=1" -d "description=Test E2E" \
    "$BASE/besoins/creer" > /dev/null

BESOIN_ID=$($MYSQL -N -e "USE rh; SELECT id FROM besoin WHERE desce='Test E2E' LIMIT 1;" 2>/dev/null)

if [ -n "$BESOIN_ID" ]; then
    ok "Besoin créé (id=$BESOIN_ID)"
else
    ko "Besoin non créé (vérifie que le poste_id=1 existe)"
fi

# -------------------------------------------------------------
step "3. RH valide le besoin (id=$BESOIN_ID)"
# -------------------------------------------------------------
if [ -n "$BESOIN_ID" ]; then
    curl -s -b "$C_RH" -c "$C_RH" -L "$BASE/besoins/valider/$BESOIN_ID" > /dev/null
    STATUT=$($MYSQL -N -e "USE rh; SELECT sb.libelle FROM besoin b JOIN statut_besoin sb ON b.statut_id=sb.id WHERE b.id=$BESOIN_ID;" 2>/dev/null)
    [ "$STATUT" = "Validé Par RH" ] && ok "Validé RH" || ko "Statut = $STATUT (attendu: Validé Par RH)"
fi

# -------------------------------------------------------------
step "4. DG valide le besoin"
# -------------------------------------------------------------
if [ -n "$BESOIN_ID" ]; then
    curl -s -b "$C_DG" -c "$C_DG" -L "$BASE/besoins/valider/$BESOIN_ID" > /dev/null
    STATUT=$($MYSQL -N -e "USE rh; SELECT sb.libelle FROM besoin b JOIN statut_besoin sb ON b.statut_id=sb.id WHERE b.id=$BESOIN_ID;" 2>/dev/null)
    [ "$STATUT" = "Validé Par DG" ] && ok "Validé DG" || ko "Statut = $STATUT (attendu: Validé Par DG)"
fi

# -------------------------------------------------------------
step "5. RH génère l'annonce"
# -------------------------------------------------------------
if [ -n "$BESOIN_ID" ]; then
    curl -s -b "$C_RH" -c "$C_RH" -L \
        -d "besoin=$BESOIN_ID" -d "desce=Annonce test E2E" \
        "$BASE/annonces/creer" > /dev/null

    ANNONCE_ID=$($MYSQL -N -e "USE rh; SELECT id FROM annonce WHERE besoin_id=$BESOIN_ID LIMIT 1;" 2>/dev/null)
    [ -n "$ANNONCE_ID" ] && ok "Annonce créée (id=$ANNONCE_ID)" || ko "Annonce non créée"
fi

# -------------------------------------------------------------
step "6. Candidat postule"
# -------------------------------------------------------------
if [ -n "$ANNONCE_ID" ]; then
    curl -s -b "$C_CANDIDAT" -c "$C_CANDIDAT" -L \
        -d "annonce_id=$ANNONCE_ID" \
        -d "titre=Dev Junior" -d "descri=Test E2E" \
        -d "email=candidat@mail.com" -d "telephone=0340000000" \
        -d "adresse=Lot II" -d "ville=Antananarivo" \
        -d "diplome[]=Licence" -d "etablissement[]=IT Univ" \
        -d "formation_debut[]=2020-09-01" -d "formation_fin[]=2023-06-30" \
        -d "poste_exp[]=" -d "entreprise[]=" -d "exp_debut[]=" -d "exp_fin[]=" \
        -d "langue_id[]=1" -d "niveau_id[]=3" \
        "$BASE/postuler" > /dev/null

    CANDIDATURE_ID=$($MYSQL -N -e "USE rh; SELECT c.id FROM candidature c JOIN cv ON c.cv_id=cv.id WHERE cv.utilisateur_id=4 ORDER BY c.id DESC LIMIT 1;" 2>/dev/null)
    [ -n "$CANDIDATURE_ID" ] && ok "Candidature créée (id=$CANDIDATURE_ID)" || ko "Candidature non créée"
fi

# -------------------------------------------------------------
step "7. RH valide la candidature"
# -------------------------------------------------------------
if [ -n "$CANDIDATURE_ID" ]; then
    curl -s -b "$C_RH" -c "$C_RH" -L -X POST "$BASE/candidatures/valider/$CANDIDATURE_ID" > /dev/null
    STATUT=$($MYSQL -N -e "USE rh; SELECT sc.libelle FROM candidature c JOIN statut_candidature sc ON c.statut_id=sc.id WHERE c.id=$CANDIDATURE_ID;" 2>/dev/null)
    [ "$STATUT" = "Accepté" ] && ok "Candidature acceptée" || ko "Statut = $STATUT (attendu: Accepté)"
fi

# -------------------------------------------------------------
step "8. RH propose un entretien (hier)"
# -------------------------------------------------------------
if [ -n "$CANDIDATURE_ID" ]; then
    HIER=$(date -d "yesterday" +"%Y-%m-%dT14:00")
    curl -s -b "$C_RH" -c "$C_RH" -L \
        -d "candidature_id=$CANDIDATURE_ID" \
        -d "date_entretien=$HIER" \
        -d "lieu=Salle A" -d "commentaire=Test E2E" \
        "$BASE/entretiens/save" > /dev/null

    ENTRETIEN_ID=$($MYSQL -N -e "USE rh; SELECT id FROM entretien WHERE candidature_id=$CANDIDATURE_ID LIMIT 1;" 2>/dev/null)
    [ -n "$ENTRETIEN_ID" ] && ok "Entretien proposé (id=$ENTRETIEN_ID)" || ko "Entretien non créé"
fi

# -------------------------------------------------------------
step "9. Candidat accepte l'entretien"
# -------------------------------------------------------------
if [ -n "$ENTRETIEN_ID" ]; then
    curl -s -b "$C_CANDIDAT" -c "$C_CANDIDAT" -L -X POST "$BASE/entretiens/accepter/$ENTRETIEN_ID" > /dev/null
    STATUT_E=$($MYSQL -N -e "USE rh; SELECT se.libelle FROM entretien e JOIN statut_entretien se ON e.statut_id=se.id WHERE e.id=$ENTRETIEN_ID;" 2>/dev/null)
    [ "$STATUT_E" = "Accepté" ] && ok "Entretien accepté" || ko "Statut entretien = $STATUT_E (attendu: Accepté)"
fi

# -------------------------------------------------------------
step "10. RH embauche le candidat"
# -------------------------------------------------------------
if [ -n "$CANDIDATURE_ID" ]; then
    curl -s -b "$C_RH" -c "$C_RH" -L -X POST "$BASE/entretiens/embaucher/$CANDIDATURE_ID" > /dev/null
    STATUT=$($MYSQL -N -e "USE rh; SELECT sc.libelle FROM candidature c JOIN statut_candidature sc ON c.statut_id=sc.id WHERE c.id=$CANDIDATURE_ID;" 2>/dev/null)
    [ "$STATUT" = "Embauché" ] && ok "Candidat embauché" || ko "Statut = $STATUT (attendu: Embauché)"
fi

# -------------------------------------------------------------
step "11. RH propose un contrat CDI"
# -------------------------------------------------------------
if [ -n "$CANDIDATURE_ID" ]; then
    curl -s -b "$C_RH" -c "$C_RH" -L \
        -d "type_contrat=CDI" \
        -d "date_debut=$(date +%Y-%m-%d)" \
        -d "date_fin=" \
        -d "salaire_negocie=3500" \
        -d "heure_debut=08:00" \
        -d "heure_fin=17:00" \
        "$BASE/contrats/save/$CANDIDATURE_ID" > /dev/null

    CONTRAT_ID=$($MYSQL -N -e "USE rh; SELECT id FROM contrat WHERE candidature_id=$CANDIDATURE_ID LIMIT 1;" 2>/dev/null)
    STATUT_CT=$($MYSQL -N -e "USE rh; SELECT statut FROM contrat WHERE id=$CONTRAT_ID;" 2>/dev/null)
    [ "$STATUT_CT" = "Proposé" ] && ok "Contrat proposé (id=$CONTRAT_ID)" || ko "Contrat statut = $STATUT_CT (attendu: Proposé)"
fi

# -------------------------------------------------------------
step "12. Candidat accepte le contrat"
# -------------------------------------------------------------
if [ -n "$CONTRAT_ID" ]; then
    curl -s -b "$C_CANDIDAT" -c "$C_CANDIDAT" -L -X POST "$BASE/contrats/accepter/$CONTRAT_ID" > /dev/null
    STATUT_CT=$($MYSQL -N -e "USE rh; SELECT statut FROM contrat WHERE id=$CONTRAT_ID;" 2>/dev/null)
    [ "$STATUT_CT" = "Accepté" ] && ok "Contrat accepté" || ko "Statut contrat = $STATUT_CT (attendu: Accepté)"
fi

# -------------------------------------------------------------
step "13. Téléchargement PDF (candidat)"
# -------------------------------------------------------------
if [ -n "$CONTRAT_ID" ]; then
    PDF_OUT="$DIR/contrat.pdf"
    HTTP_CODE=$(curl -s -L -b "$C_CANDIDAT" -o "$PDF_OUT" -w "%{http_code}" "$BASE/contrats/pdf/$CONTRAT_ID")
    
    if [ "$HTTP_CODE" = "200" ] && head -c 4 "$PDF_OUT" | grep -q "%PDF"; then
        ok "PDF téléchargé avec succès ($(du -h "$PDF_OUT" | cut -f1))"
    else
        ko "PDF invalide (Code HTTP: $HTTP_CODE)"
    fi
fi

# -------------------------------------------------------------
# FIN DES TESTS
# -------------------------------------------------------------
echo -e "\n=============================================="
echo -e "RÉSULTAT FINAL : ${GREEN}$PASS OK${NC} / ${RED}$FAIL KO${NC}"
echo -e "=============================================="

if [ "$FAIL" -eq 0 ]; then
    echo -e "${GREEN}🎉 Félicitations ! Tout le flux de recrutement fonctionne parfaitement.${NC}"
    echo -e "Le PDF de test est disponible ici : $DIR/contrat.pdf"
    exit 0
else
    echo -e "${RED}⚠️ Certains tests ont échoué. Vérifie les logs ou la base de données.${NC}"
    exit 1
fi
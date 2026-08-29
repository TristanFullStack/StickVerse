import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'chargement',
        'erreur',
        'reessayerButton',
        'information',
        'salon',
        'combatActif',
        'combatActifId',
        'combatStatut',
        'numeroRound',
        'etatRound',
        'finCombat',
        'finCombatTitre',
        'finCombatMessage',
        'rapportFinalLink',
        'resultatRound',
        'resultatRoundNumero',
        'resultatRoundLignes',
        'historiqueRounds',
        'historiqueRoundsListe',
        'attenteAdversaire',
        'participants',
        'moiNom',
        'moiCombattants',
        'adversaireNom',
        'adversaireCombattants',
        'planSection',
        'planForm',
        'cibleAttaqueX',
        'cibleDefenseX',
        'cibleAttaqueY',
        'cibleDefenseY',
        'envoyerPlanButton',
        'abandonButton',
        'annulerButton',
        'equipeSelect',
        'equipeApercu',
        'creerButton',
        'aucunCombat',
        'combatsDisponibles',
        'aucunHistoriqueCombat',
        'historiqueCombats',
    ];

    static values = {
        salonUrl: String,
        creerUrl: String,
        rejoindreUrlModele: String,
        combatUrlModele: String,
        planUrlModele: String,
        abandonUrlModele: String,
        annulerUrlModele: String,
        rapportUrlModele: String,
        imagesBaseUrl: String,
    };

    connect() {
        this.salon = null;
        this.combat = null;
        this.combatActifIdCourant = null;
        this.requeteEnCours = null;
        this.minuterieActualisation = null;
        this.actionEnCours = false;
        this.etatsInteractions = new Map();
        this.chargerSalon();
    }

    disconnect() {
        this.requeteEnCours?.abort();
        this.annulerActualisation();
    }

    rafraichir() {
        if (this.actionEnCours) {
            return;
        }

        if (this.combatActifIdCourant !== null) {
            this.chargerCombat(this.combatActifIdCourant);

            return;
        }

        this.chargerSalon();
    }

    async retournerSalon() {
        if (this.actionEnCours) {
            return;
        }

        this.combatActifIdCourant = null;
        this.combat = null;
        this.annulerActualisation();
        await this.chargerSalon();
    }

    changerEquipe() {
        this.afficherEquipeSelectionnee();
    }

    async soumettrePlan(event) {
        event.preventDefault();

        const combatId = this.combatActifIdCourant;
        const plan = {
            cibleAttaqueX: this.cibleAttaqueXTarget.value,
            cibleAttaqueY: this.cibleAttaqueYTarget.value,
            cibleDefenseX: this.cibleDefenseXTarget.value,
            cibleDefenseY: this.cibleDefenseYTarget.value,
        };

        if (combatId === null) {
            this.afficherErreur('Aucun combat actif n’est disponible.');

            return;
        }

        if (Object.values(plan).some((cible) => cible === '')) {
            this.afficherErreur(
                'Les quatre cibles du plan sont obligatoires.'
            );

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.remplacerCombatId(
                    this.planUrlModeleValue,
                    combatId,
                ),
                plan,
                this.combat?.csrf?.plan,
            );

            this.planFormTarget.reset();
            await this.chargerCombat(combatId);
        });
    }

    async abandonnerCombat() {
        const combatId = this.combatActifIdCourant;

        if (combatId === null) {
            this.afficherErreur('Aucun combat actif n’est disponible.');

            return;
        }

        const confirmation = window.confirm([
            'Abandonner le combat ?',
            'Cette action est définitive et ton adversaire gagnera.',
        ].join('\n'));

        if (!confirmation) {
            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.remplacerCombatId(
                    this.abandonUrlModeleValue,
                    combatId,
                ),
                {},
                this.combat?.csrf?.abandon,
            );

            await this.chargerCombat(combatId);
        });
    }

    async annulerCombat() {
        const combatId = this.combatActifIdCourant;

        if (combatId === null) {
            this.afficherErreur('Aucun combat en attente n’est disponible.');

            return;
        }

        const confirmation = window.confirm([
            'Annuler ce combat en attente ?',
            'Il disparaîtra du salon et tu pourras en créer ou rejoindre un autre.',
        ].join('\n'));

        if (!confirmation) {
            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.remplacerCombatId(
                    this.annulerUrlModeleValue,
                    combatId,
                ),
                {},
                this.combat?.csrf?.annuler,
            );

            this.combatActifIdCourant = null;
            this.combat = null;
            await this.chargerSalon();
            this.afficherInformation(
                'Le combat en attente a bien été annulé.'
            );
        });
    }

    async creerCombat() {
        const equipeId = this.equipeSelectionneeId();

        if (equipeId === null) {
            this.afficherErreur(
                'Sélectionne une équipe avant de créer un combat.'
            );

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.creerUrlValue,
                { equipeId },
                this.salon?.csrf?.creer,
            );

            await this.chargerSalon();
        });
    }

    async rejoindreCombat(event) {
        const equipeId = this.equipeSelectionneeId();
        const combatId = Number.parseInt(
            event.currentTarget.dataset.combatId,
            10,
        );

        if (equipeId === null) {
            this.afficherErreur(
                'Sélectionne une équipe avant de rejoindre un combat.'
            );

            return;
        }

        if (!Number.isInteger(combatId) || combatId <= 0) {
            this.afficherErreur('Le combat sélectionné est invalide.');

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.remplacerCombatId(
                    this.rejoindreUrlModeleValue,
                    combatId,
                ),
                { equipeId },
                this.salon?.csrf?.rejoindre,
            );

            await this.chargerSalon();
        });
    }

    async chargerSalon() {
        this.requeteEnCours?.abort();

        const requete = new AbortController();
        this.requeteEnCours = requete;
        this.chargementTarget.textContent = 'Chargement du salon…';
        this.chargementTarget.hidden = false;
        this.masquerErreur();

        try {
            const reponse = await fetch(this.salonUrlValue, {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
                signal: requete.signal,
            });

            const donnees = await this.lireJson(reponse);

            if (!reponse.ok) {
                throw new Error(
                    donnees.erreur
                    ?? 'Le salon est momentanément indisponible.'
                );
            }

            this.salon = donnees;
            await this.afficherSalon();
        } catch (erreur) {
            if (erreur.name !== 'AbortError') {
                this.afficherErreur(
                    this.messageErreur(
                        erreur,
                        'Impossible de charger le salon.',
                    ),
                    true,
                );
                this.programmerNouvelEssaiSalon();
            }
        } finally {
            if (this.requeteEnCours === requete) {
                this.requeteEnCours = null;
                this.chargementTarget.hidden = true;
            }
        }
    }

    async afficherSalon() {
        const combatActifId = this.entierPositif(
            this.salon?.combatActifId
        );

        if (combatActifId !== null) {
            this.combatActifIdCourant = combatActifId;
            this.combatActifIdTarget.textContent = String(combatActifId);
            this.combatActifTarget.hidden = false;
            this.salonTarget.hidden = true;
            await this.chargerCombat(combatActifId);

            return;
        }

        this.combatActifIdCourant = null;
        this.combat = null;
        this.annulerActualisation();
        this.combatActifTarget.hidden = true;
        this.salonTarget.hidden = false;
        this.afficherEquipes();
        this.afficherCombatsDisponibles();
        this.afficherHistoriqueCombats();
    }

    async chargerCombat(combatId) {
        this.annulerActualisation();
        this.requeteEnCours?.abort();

        const requete = new AbortController();
        this.requeteEnCours = requete;
        this.chargementTarget.textContent = 'Actualisation du combat…';
        this.chargementTarget.hidden = false;
        this.masquerErreur();

        try {
            const reponse = await fetch(
                this.remplacerCombatId(
                    this.combatUrlModeleValue,
                    combatId,
                ),
                {
                    headers: {
                        Accept: 'application/json',
                    },
                    credentials: 'same-origin',
                    signal: requete.signal,
                },
            );

            const donnees = await this.lireJson(reponse);

            if (!reponse.ok) {
                throw new Error(
                    donnees.erreur
                    ?? 'Le combat est momentanément indisponible.'
                );
            }

            if (donnees.expirationAutomatique === true) {
                this.combatActifIdCourant = null;
                this.combat = null;
                await this.chargerSalon();
                this.afficherInformation([
                    'Le combat a été annulé automatiquement',
                    'après 5 minutes sans adversaire.',
                ].join(' '));

                return;
            }

            this.combat = donnees;
            this.afficherCombat();
            this.programmerActualisation();
        } catch (erreur) {
            if (erreur.name !== 'AbortError') {
                this.afficherErreur(
                    this.messageErreur(
                        erreur,
                        'Impossible de charger le combat.',
                    ),
                    true,
                );
                this.programmerNouvelEssaiCombat(combatId);
            }
        } finally {
            if (this.requeteEnCours === requete) {
                this.requeteEnCours = null;
                this.chargementTarget.hidden = true;
            }
        }
    }

    afficherCombat() {
        const statuts = {
            en_attente: 'En attente',
            en_cours: 'En cours',
            termine: 'Terminé',
            abandonne: 'Abandonné',
            annule: 'Annulé',
        };

        this.combatStatutTarget.textContent = statuts[this.combat.statut]
            ?? this.combat.statut
            ?? 'Inconnu';
        this.numeroRoundTarget.textContent = String(
            this.combat.numeroRound ?? '—'
        );

        this.afficherParticipant(
            this.combat.moi,
            this.moiNomTarget,
            this.moiCombattantsTarget,
            'Ton équipe',
        );

        const adversairePresent = this.combat.adversaire !== null;
        this.attenteAdversaireTarget.hidden = adversairePresent;

        this.afficherParticipant(
            this.combat.adversaire,
            this.adversaireNomTarget,
            this.adversaireCombattantsTarget,
            'Équipe adverse',
        );

        this.afficherEtatRound();
        this.afficherFinCombat();
        this.afficherDernierRound();
        this.afficherHistoriqueRounds();
        this.afficherFormulairePlan();
        this.afficherBoutonAbandon();
        this.afficherBoutonAnnulation();
    }

    afficherBoutonAbandon() {
        this.abandonButtonTarget.hidden = !(
            this.combat.statut === 'en_cours'
            && this.combat.adversaire !== null
        );
    }

    afficherBoutonAnnulation() {
        this.annulerButtonTarget.hidden = !(
            this.combat.statut === 'en_attente'
            && this.combat.adversaire === null
        );
    }

    afficherFinCombat() {
        const estTermine = this.combat.statut === 'termine'
            || this.combat.statut === 'abandonne'
            || this.combat.statut === 'annule';

        if (!estTermine) {
            this.finCombatTarget.hidden = true;
            this.finCombatTarget.dataset.resultat = '';
            this.rapportFinalLinkTarget.hidden = true;
            this.rapportFinalLinkTarget.removeAttribute('href');

            return;
        }

        const moiId = this.combat.moi?.id;
        const gagnantId = this.combat.gagnantId;
        const victoire = gagnantId !== null && gagnantId === moiId;
        let titre;
        let message;
        let resultat;

        if (this.combat.statut === 'annule') {
            titre = 'Combat annulé';
            message = 'Ce combat en attente a été annulé.';
            resultat = 'annulation';
        } else if (this.combat.statut === 'abandonne') {
            if (victoire) {
                titre = 'Victoire par abandon';
                message = 'Ton adversaire a abandonné le combat.';
                resultat = 'victoire';
            } else if (gagnantId !== null) {
                titre = 'Défaite par abandon';
                message = 'Tu as abandonné : ton adversaire gagne.';
                resultat = 'defaite';
            } else {
                titre = 'Combat abandonné';
                message = 'Le combat a été interrompu.';
                resultat = 'abandon';
            }
        } else if (gagnantId === null) {
            titre = 'Match nul';
            message = 'Les deux équipes ont été éliminées au même round.';
            resultat = 'match-nul';
        } else if (victoire) {
            titre = 'Victoire';
            message = 'Ton équipe remporte le combat.';
            resultat = 'victoire';
        } else {
            titre = 'Défaite';
            message = 'L’équipe adverse remporte le combat.';
            resultat = 'defaite';
        }

        this.finCombatTitreTarget.textContent = titre;
        this.finCombatMessageTarget.textContent = message;
        this.finCombatTarget.dataset.resultat = resultat;
        this.rapportFinalLinkTarget.hidden =
            this.combat.statut === 'annule';

        if (this.combat.statut === 'annule') {
            this.rapportFinalLinkTarget.removeAttribute('href');
        } else {
            this.rapportFinalLinkTarget.href = this.remplacerCombatId(
                this.rapportUrlModeleValue,
                this.combat.combatId,
            );
        }

        this.finCombatTarget.hidden = false;
    }

    afficherDernierRound() {
        const dernierRound = this.combat?.dernierRound;
        const resultats = dernierRound?.resultats;

        this.resultatRoundLignesTarget.replaceChildren();

        if (
            dernierRound === null
            || typeof dernierRound !== 'object'
            || resultats === null
            || typeof resultats !== 'object'
            || Array.isArray(resultats)
        ) {
            this.resultatRoundTarget.hidden = true;

            return;
        }

        const positionMoi = dernierRound.positionMoi;
        const lignes = this.lignesResultatRound(
            resultats,
            positionMoi,
        );

        if (lignes.length === 0) {
            this.resultatRoundTarget.hidden = true;

            return;
        }

        this.resultatRoundNumeroTarget.textContent = String(
            dernierRound.numero ?? '—'
        );

        for (const ligne of lignes) {
            const element = document.createElement('tr');
            const cible = ligne.proprietaire === positionMoi
                ? `Ton équipe — ${ligne.slot}`
                : `Équipe adverse — ${ligne.slot}`;

            for (const valeur of [
                cible,
                ligne.resultat.attaque,
                ligne.resultat.defense,
                ligne.resultat.degatsEffectifs,
                ligne.resultat.pvRestants,
            ]) {
                const cellule = document.createElement('td');
                cellule.textContent = valeur ?? '—';
                element.append(cellule);
            }

            this.resultatRoundLignesTarget.append(element);
        }

        this.resultatRoundTarget.hidden = false;
    }

    afficherHistoriqueRounds() {
        const historique = Array.isArray(this.combat?.historiqueRounds)
            ? this.combat.historiqueRounds
            : [];
        const dernierNumero = this.entierPositif(
            this.combat?.dernierRound?.numero
        );
        const positionMoi = this.combat?.dernierRound?.positionMoi;
        const roundsOuverts = new Set(
            Array.from(
                this.historiqueRoundsListeTarget.querySelectorAll(
                    '.historique-round[open]'
                )
            ).map((panneau) => panneau.dataset.numeroRound)
        );

        this.historiqueRoundsListeTarget.replaceChildren();

        const roundsPrecedents = historique
            .map((round) => {
                const numero = this.entierPositif(round?.numero);
                const lignes = this.lignesResultatRound(
                    round?.resultats,
                    positionMoi,
                );

                if (
                    numero === null
                    || numero === dernierNumero
                    || lignes.length === 0
                ) {
                    return null;
                }

                return { numero, lignes };
            })
            .filter((round) => round !== null)
            .sort((roundA, roundB) => roundA.numero - roundB.numero);

        if (roundsPrecedents.length === 0) {
            this.historiqueRoundsTarget.hidden = true;

            return;
        }

        for (const round of roundsPrecedents) {
            const panneau = document.createElement('details');
            const resume = document.createElement('summary');
            const titre = document.createElement('span');
            const statistiques = document.createElement('span');
            const degats = this.resumerDegatsRound(
                round.lignes,
                positionMoi,
            );

            panneau.className = 'historique-round';
            panneau.dataset.numeroRound = String(round.numero);
            panneau.open = roundsOuverts.has(String(round.numero));
            titre.className = 'historique-round-titre';
            titre.textContent = `Round ${round.numero}`;
            statistiques.className = 'historique-round-statistiques';
            statistiques.textContent = [
                `${degats.infliges} dégâts infligés`,
                `${degats.recus} dégâts reçus`,
            ].join(' · ');

            resume.append(titre, statistiques);
            panneau.append(
                resume,
                this.creerTableauHistoriqueRound(
                    round.numero,
                    round.lignes,
                    positionMoi,
                ),
            );
            this.historiqueRoundsListeTarget.append(panneau);
        }

        this.historiqueRoundsTarget.hidden = false;
    }

    lignesResultatRound(resultats, positionMoi) {
        if (
            resultats === null
            || typeof resultats !== 'object'
            || Array.isArray(resultats)
        ) {
            return [];
        }

        return Object.entries(resultats)
            .map(([cible, resultat]) => {
                const correspondance = cible.match(
                    /^(joueur1|joueur2)_([A-D])$/
                );

                if (
                    correspondance === null
                    || resultat === null
                    || typeof resultat !== 'object'
                    || Array.isArray(resultat)
                ) {
                    return null;
                }

                return {
                    proprietaire: correspondance[1],
                    slot: correspondance[2],
                    resultat,
                };
            })
            .filter((ligne) => ligne !== null)
            .sort((ligneA, ligneB) => {
                const ordreA = ligneA.proprietaire === positionMoi ? 0 : 1;
                const ordreB = ligneB.proprietaire === positionMoi ? 0 : 1;

                return ordreA - ordreB
                    || ligneA.slot.localeCompare(ligneB.slot);
            });
    }

    resumerDegatsRound(lignes, positionMoi) {
        const resume = {
            infliges: 0,
            recus: 0,
        };

        for (const ligne of lignes) {
            const degats = Number(ligne.resultat.degatsEffectifs);

            if (!Number.isFinite(degats)) {
                continue;
            }

            if (ligne.proprietaire === positionMoi) {
                resume.recus += degats;
            } else {
                resume.infliges += degats;
            }
        }

        return resume;
    }

    creerTableauHistoriqueRound(numero, lignes, positionMoi) {
        const conteneur = document.createElement('div');
        const tableau = document.createElement('table');
        const legende = document.createElement('caption');
        const entete = document.createElement('thead');
        const ligneEntete = document.createElement('tr');
        const corps = document.createElement('tbody');

        conteneur.className = 'historique-round-tableau';
        legende.className = 'historique-round-legende';
        legende.textContent = `Détails du round ${numero}`;

        for (const libelle of [
            'Cible',
            'Attaque',
            'Défense',
            'Dégâts',
            'PV restants',
        ]) {
            const cellule = document.createElement('th');
            cellule.scope = 'col';
            cellule.textContent = libelle;
            ligneEntete.append(cellule);
        }

        entete.append(ligneEntete);

        for (const ligne of lignes) {
            const element = document.createElement('tr');
            const cible = ligne.proprietaire === positionMoi
                ? `Ton équipe — ${ligne.slot}`
                : `Équipe adverse — ${ligne.slot}`;

            for (const valeur of [
                cible,
                ligne.resultat.attaque,
                ligne.resultat.defense,
                ligne.resultat.degatsEffectifs,
                ligne.resultat.pvRestants,
            ]) {
                const cellule = document.createElement('td');
                cellule.textContent = valeur ?? '—';
                element.append(cellule);
            }

            corps.append(element);
        }

        tableau.append(legende, entete, corps);
        conteneur.append(tableau);

        return conteneur;
    }

    afficherFormulairePlan() {
        const peutJouer = this.combat.statut === 'en_cours'
            && this.combat.adversaire !== null
            && this.combat.planSoumis === false;

        this.planSectionTarget.hidden = !peutJouer;

        if (!peutJouer) {
            return;
        }

        const ciblesAttaque = this.combattantsVivants(
            this.combat.adversaire
        );
        const ciblesDefense = this.combattantsVivants(
            this.combat.moi
        );

        this.remplirSelectCibles(
            this.cibleAttaqueXTarget,
            ciblesAttaque,
        );
        this.remplirSelectCibles(
            this.cibleAttaqueYTarget,
            ciblesAttaque,
        );
        this.remplirSelectCibles(
            this.cibleDefenseXTarget,
            ciblesDefense,
        );
        this.remplirSelectCibles(
            this.cibleDefenseYTarget,
            ciblesDefense,
        );

        this.envoyerPlanButtonTarget.disabled =
            ciblesAttaque.length === 0
            || ciblesDefense.length === 0;
    }

    combattantsVivants(participant) {
        if (!Array.isArray(participant?.combattants)) {
            return [];
        }

        return participant.combattants.filter((combattant) => {
            const pvActuels = Number.parseInt(
                combattant.pvActuels,
                10,
            );

            return combattant.vivant !== false
                && Number.isInteger(pvActuels)
                && pvActuels > 0;
        });
    }

    remplirSelectCibles(select, combattants) {
        const valeurPrecedente = select.value;
        select.replaceChildren();

        const invitation = document.createElement('option');
        invitation.value = '';
        invitation.textContent = 'Sélectionner une cible';
        invitation.disabled = true;
        invitation.selected = true;
        select.append(invitation);

        for (const combattant of combattants) {
            const option = document.createElement('option');
            option.value = combattant.slot;
            option.textContent = [
                combattant.slot,
                combattant.nom ?? 'Stickman',
                `${combattant.pvActuels} PV`,
            ].join(' — ');
            select.append(option);
        }

        if (
            valeurPrecedente !== ''
            && combattants.some(
                (combattant) => combattant.slot === valeurPrecedente
            )
        ) {
            select.value = valeurPrecedente;
        }

        select.disabled = combattants.length === 0;
    }

    afficherParticipant(participant, nomTarget, listeTarget, libelle) {
        const pourcentagesPrecedents = new Map(
            Array.from(
                listeTarget.querySelectorAll(
                    '.carte-combattant[data-slot] .carte-combattant-vie-barre'
                )
            ).map((barre) => [
                barre.closest('.carte-combattant')?.dataset.slot,
                Number.parseFloat(barre.dataset.pourcentage),
            ])
        );

        listeTarget.replaceChildren();

        if (!participant) {
            nomTarget.textContent = libelle;

            const message = document.createElement('p');
            message.textContent = 'Composition en attente.';
            listeTarget.append(message);

            return;
        }

        nomTarget.textContent = participant.email ?? libelle;

        const combattants = Array.isArray(participant.combattants)
            ? participant.combattants
            : [];

        for (const combattant of combattants) {
            listeTarget.append(
                this.creerCarteCombattant(
                    combattant,
                    pourcentagesPrecedents.get(combattant.slot),
                )
            );
        }
    }

    afficherEtatRound() {
        if (this.combat.statut === 'annule') {
            this.etatRoundTarget.textContent =
                'Le combat en attente a été annulé.';

            return;
        }

        if (this.combat.statut === 'en_attente') {
            this.etatRoundTarget.textContent = [
                'Ton équipe est enregistrée.',
                'Le combat commencera dès qu’un adversaire le rejoindra.',
            ].join(' ');

            return;
        }

        if (
            this.combat.statut === 'termine'
            || this.combat.statut === 'abandonne'
            || this.combat.statut === 'annule'
        ) {
            this.etatRoundTarget.textContent =
                'Le résultat final a été enregistré par le serveur.';

            return;
        }

        if (this.combat.planSoumis) {
            this.etatRoundTarget.textContent = this.combat.adversairePret
                ? 'Les deux plans sont prêts.'
                : 'Ton plan est envoyé. En attente du plan adverse.';

            return;
        }

        this.etatRoundTarget.textContent =
            'Les deux joueurs sont prêts à préparer leur plan secret.';
    }

    programmerActualisation() {
        this.annulerActualisation();

        if (
            this.actionEnCours
            || (
            this.combat?.statut !== 'en_attente'
            && this.combat?.statut !== 'en_cours'
            )
        ) {
            return;
        }

        this.minuterieActualisation = window.setTimeout(() => {
            if (this.combatActifIdCourant !== null) {
                this.chargerCombat(this.combatActifIdCourant);
            }
        }, 3000);
    }

    programmerNouvelEssaiSalon() {
        this.annulerActualisation();

        if (this.actionEnCours) {
            return;
        }

        this.minuterieActualisation = window.setTimeout(() => {
            if (this.combatActifIdCourant === null) {
                this.chargerSalon();
            }
        }, 5000);
    }

    programmerNouvelEssaiCombat(combatId) {
        this.annulerActualisation();

        if (this.actionEnCours) {
            return;
        }

        this.minuterieActualisation = window.setTimeout(() => {
            if (this.combatActifIdCourant === combatId) {
                this.chargerCombat(combatId);
            }
        }, 5000);
    }

    annulerActualisation() {
        if (this.minuterieActualisation !== null) {
            window.clearTimeout(this.minuterieActualisation);
            this.minuterieActualisation = null;
        }
    }

    afficherEquipes() {
        const equipes = Array.isArray(this.salon?.equipes)
            ? this.salon.equipes
            : [];
        const equipePrecedente = this.equipeSelectTarget.value;

        this.equipeSelectTarget.replaceChildren();

        for (const equipe of equipes) {
            const equipeId = this.entierPositif(equipe.id);

            if (equipeId === null) {
                continue;
            }

            const option = document.createElement('option');
            option.value = String(equipeId);
            option.textContent = equipe.nom ?? `Équipe #${equipeId}`;
            this.equipeSelectTarget.append(option);
        }

        if (
            equipePrecedente !== ''
            && equipes.some(
                (equipe) => String(equipe.id) === equipePrecedente
            )
        ) {
            this.equipeSelectTarget.value = equipePrecedente;
        }

        const aucuneEquipe = this.equipeSelectTarget.options.length === 0;
        this.equipeSelectTarget.disabled = aucuneEquipe;
        this.creerButtonTarget.disabled = aucuneEquipe;
        this.afficherEquipeSelectionnee();
    }

    afficherEquipeSelectionnee() {
        this.equipeApercuTarget.replaceChildren();

        const equipeId = this.equipeSelectionneeId();
        const equipes = Array.isArray(this.salon?.equipes)
            ? this.salon.equipes
            : [];
        const equipe = equipes.find(
            (element) => this.entierPositif(element.id) === equipeId
        );

        if (!equipe) {
            const message = document.createElement('p');
            message.textContent = 'Aucune équipe complète disponible.';
            this.equipeApercuTarget.append(message);

            return;
        }

        const titre = document.createElement('h3');
        titre.textContent = equipe.nom ?? `Équipe #${equipeId}`;
        titre.className = 'selection-equipe-titre';
        this.equipeApercuTarget.append(titre);

        const liste = document.createElement('div');
        liste.className = 'grille-combattants';
        const combattants = Array.isArray(equipe.combattants)
            ? equipe.combattants
            : [];

        for (const combattant of combattants) {
            liste.append(this.creerCarteCombattant(combattant));
        }

        this.equipeApercuTarget.append(liste);
    }

    creerCarteCombattant(combattant, pourcentagePrecedent = null) {
        const carte = document.createElement('article');
        const titre = document.createElement('h4');
        const image = document.createElement('img');
        const vie = document.createElement('div');
        const vieInformations = document.createElement('div');
        const vieValeur = document.createElement('span');
        const vieEtat = document.createElement('span');
        const vieBarre = document.createElement('div');
        const vieRemplissage = document.createElement('span');
        const statistiques = document.createElement('p');

        carte.className = 'carte-combattant';
        titre.className = 'carte-combattant-titre';
        image.className = 'carte-combattant-image';
        vie.className = 'carte-combattant-vie';
        vieInformations.className = 'carte-combattant-vie-informations';
        vieValeur.className = 'carte-combattant-vie-valeur';
        vieEtat.className = 'carte-combattant-vie-etat';
        vieBarre.className = 'carte-combattant-vie-barre';
        vieRemplissage.className = 'carte-combattant-vie-remplissage';
        statistiques.className = 'carte-combattant-statistiques';
        titre.textContent = [
            combattant.slot ?? '?',
            combattant.nom ?? 'Stickman',
        ].join(' — ');
        image.alt = combattant.nom ?? 'Stickman';
        image.loading = 'lazy';
        carte.dataset.slot = combattant.slot ?? '';
        carte.dataset.vivant = combattant.vivant === false
            ? 'false'
            : 'true';

        if (typeof combattant.image === 'string') {
            image.src = [
                this.imagesBaseUrlValue,
                encodeURIComponent(combattant.image),
            ].join('/');
        }

        const pvMaximumBruts = Number(
            combattant.pvMaximum ?? combattant.pv
        );
        const pvActuelsBruts = Number(
            combattant.pvActuels ?? combattant.pv
        );
        const pvMaximum = Number.isFinite(pvMaximumBruts)
            && pvMaximumBruts > 0
            ? Math.round(pvMaximumBruts)
            : 0;
        const pvActuels = Number.isFinite(pvActuelsBruts)
            ? Math.min(
                pvMaximum,
                Math.max(0, Math.round(pvActuelsBruts)),
            )
            : 0;
        const pourcentageVie = pvMaximum > 0
            ? Math.round((pvActuels / pvMaximum) * 100)
            : 0;
        const estKo = combattant.vivant === false || pvActuels === 0;
        let etatVie = 'normal';
        let libelleEtatVie = 'Stable';

        if (estKo) {
            etatVie = 'ko';
            libelleEtatVie = 'K.O.';
        } else if (pourcentageVie <= 25) {
            etatVie = 'critique';
            libelleEtatVie = 'Critique';
        } else if (pourcentageVie <= 50) {
            etatVie = 'blesse';
            libelleEtatVie = 'Blessé';
        }

        carte.dataset.etatVie = etatVie;
        carte.classList.add(`est-${etatVie}`);

        if (estKo) {
            carte.dataset.vivant = 'false';
        }

        vieValeur.textContent = `PV ${pvActuels} / ${pvMaximum}`;
        vieEtat.textContent = libelleEtatVie;
        vieBarre.dataset.pourcentage = String(pourcentageVie);
        vieBarre.setAttribute('role', 'progressbar');
        vieBarre.setAttribute(
            'aria-label',
            `Points de vie de ${combattant.nom ?? 'Stickman'}`,
        );
        vieBarre.setAttribute('aria-valuemin', '0');
        vieBarre.setAttribute('aria-valuemax', String(pvMaximum));
        vieBarre.setAttribute('aria-valuenow', String(pvActuels));
        vieBarre.setAttribute(
            'aria-valuetext',
            `${pvActuels} points de vie sur ${pvMaximum} — ${libelleEtatVie}`,
        );

        const ancienPourcentage = Number.isFinite(pourcentagePrecedent)
            ? Math.min(100, Math.max(0, pourcentagePrecedent))
            : pourcentageVie;

        vieRemplissage.style.width = `${ancienPourcentage}%`;

        if (ancienPourcentage !== pourcentageVie) {
            requestAnimationFrame(() => {
                vieRemplissage.style.width = `${pourcentageVie}%`;
            });
        }

        vieInformations.append(vieValeur, vieEtat);
        vieBarre.append(vieRemplissage);
        vie.append(vieInformations, vieBarre);

        statistiques.textContent = [
            `ATQ ${combattant.attaque ?? '—'}`,
            `DÉF ${combattant.defense ?? '—'}`,
        ].join(' · ');

        carte.append(titre, image, vie, statistiques);

        return carte;
    }

    afficherCombatsDisponibles() {
        const combats = Array.isArray(this.salon?.combatsDisponibles)
            ? this.salon.combatsDisponibles
            : [];

        this.combatsDisponiblesTarget.replaceChildren();
        this.aucunCombatTarget.hidden = combats.length > 0;

        for (const combat of combats) {
            const combatId = this.entierPositif(combat.id);

            if (combatId === null) {
                continue;
            }

            const ligne = document.createElement('article');
            const titre = document.createElement('h3');
            const details = document.createElement('p');
            const bouton = document.createElement('button');

            ligne.className = 'combat-disponible';
            titre.textContent = `Combat #${combatId}`;
            details.textContent = combat.joueur1Email
                ?? `Joueur #${combat.joueur1Id ?? '—'}`;
            bouton.type = 'button';
            bouton.textContent = 'Rejoindre';
            bouton.dataset.combatId = String(combatId);
            bouton.dataset.action = 'combat-en-ligne#rejoindreCombat';

            ligne.append(titre, details, bouton);
            this.combatsDisponiblesTarget.append(ligne);
        }
    }

    afficherHistoriqueCombats() {
        const historique = Array.isArray(this.salon?.historiqueCombats)
            ? this.salon.historiqueCombats
            : [];

        this.historiqueCombatsTarget.replaceChildren();
        this.aucunHistoriqueCombatTarget.hidden = historique.length > 0;

        const libellesResultat = {
            victoire: 'Victoire',
            defaite: 'Défaite',
            egalite: 'Égalité',
            victoire_abandon: 'Victoire par abandon',
            abandon: 'Abandon',
        };

        for (const combat of historique) {
            const combatId = this.entierPositif(combat.id);

            if (combatId === null) {
                continue;
            }

            const carte = document.createElement('article');
            const entete = document.createElement('div');
            const titre = document.createElement('h3');
            const resultat = document.createElement('strong');
            const adversaire = document.createElement('p');
            const informations = document.createElement('p');
            const rapport = document.createElement('a');
            const nombreRounds = Number.isInteger(combat.nombreRounds)
                && combat.nombreRounds >= 0
                ? combat.nombreRounds
                : 0;
            const resultatCode = Object.hasOwn(
                libellesResultat,
                combat.resultat,
            )
                ? combat.resultat
                : 'egalite';

            carte.className = 'historique-combat';
            carte.dataset.resultat = resultatCode;
            entete.className = 'historique-combat-entete';
            resultat.className = 'historique-combat-resultat';
            titre.textContent = `Combat #${combatId}`;
            resultat.textContent = libellesResultat[resultatCode];
            adversaire.textContent = [
                'Adversaire :',
                combat.adversaireEmail ?? 'inconnu',
            ].join(' ');
            informations.textContent = [
                `${nombreRounds} round${nombreRounds > 1 ? 's' : ''} joué${nombreRounds > 1 ? 's' : ''}`,
                this.formaterDateCombat(combat.dateFin),
            ].join(' · ');
            rapport.className = 'historique-combat-rapport';
            rapport.href = this.remplacerCombatId(
                this.rapportUrlModeleValue,
                combatId,
            );
            rapport.textContent = 'Voir le rapport';

            entete.append(titre, resultat);
            carte.append(entete, adversaire, informations, rapport);
            this.historiqueCombatsTarget.append(carte);
        }
    }

    formaterDateCombat(dateFin) {
        if (typeof dateFin !== 'string') {
            return 'Date inconnue';
        }

        const date = new Date(dateFin);

        if (Number.isNaN(date.getTime())) {
            return 'Date inconnue';
        }

        return new Intl.DateTimeFormat(
            'fr-FR',
            {
                dateStyle: 'short',
                timeStyle: 'short',
            },
        ).format(date);
    }

    equipeSelectionneeId() {
        return this.entierPositif(this.equipeSelectTarget.value);
    }

    entierPositif(valeur) {
        const entier = Number.parseInt(valeur, 10);

        return Number.isInteger(entier) && entier > 0
            ? entier
            : null;
    }

    remplacerCombatId(modele, combatId) {
        return modele.replace(
            '__combat_id__',
            encodeURIComponent(String(combatId)),
        );
    }

    async executerAction(action) {
        if (this.actionEnCours) {
            return;
        }

        this.actionEnCours = true;
        this.annulerActualisation();
        this.requeteEnCours?.abort();
        this.requeteEnCours = null;
        this.chargementTarget.hidden = true;
        this.element.setAttribute('aria-busy', 'true');
        this.masquerErreur();
        this.masquerInformation();
        this.basculerBoutons(true);

        try {
            await action();
        } catch (erreur) {
            this.afficherErreur(
                this.messageErreur(
                    erreur,
                    'L’action demandée a échoué.',
                ),
                erreur instanceof TypeError
                    || window.navigator.onLine === false,
            );
        } finally {
            this.basculerBoutons(false);
            this.element.setAttribute('aria-busy', 'false');
            this.actionEnCours = false;
            this.programmerActualisation();
        }
    }

    async envoyerJson(url, donnees, jetonCsrf) {
        if (typeof jetonCsrf !== 'string' || jetonCsrf === '') {
            throw new Error(
                'Le jeton de sécurité est absent. Actualise la page.'
            );
        }

        const reponse = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': jetonCsrf,
            },
            credentials: 'same-origin',
            body: JSON.stringify(donnees),
        });

        const resultat = await this.lireJson(reponse);

        if (!reponse.ok) {
            throw new Error(
                resultat.erreur
                ?? 'Le serveur a refusé l’action.'
            );
        }

        return resultat;
    }

    async lireJson(reponse) {
        if (reponse.redirected) {
            const destination = new URL(
                reponse.url,
                window.location.origin,
            );

            if (destination.pathname.startsWith('/login')) {
                throw new Error(
                    'Ta session a expiré. Recharge la page pour te reconnecter.'
                );
            }
        }

        const typeContenu = reponse.headers.get('content-type') ?? '';

        if (!typeContenu.includes('application/json')) {
            throw new Error('La réponse du serveur est invalide.');
        }

        return reponse.json();
    }

    messageErreur(erreur, messageParDefaut) {
        if (window.navigator.onLine === false) {
            return [
                'Connexion internet interrompue.',
                'La page réessaiera automatiquement.',
            ].join(' ');
        }

        if (erreur instanceof TypeError) {
            return [
                'Impossible de contacter le serveur.',
                'Vérifie ta connexion puis réessaie.',
            ].join(' ');
        }

        return typeof erreur?.message === 'string'
            && erreur.message !== ''
            ? erreur.message
            : messageParDefaut;
    }

    basculerBoutons(desactiver) {
        if (desactiver) {
            for (const interaction of this.element.querySelectorAll(
                'button, select'
            )) {
                if (!this.etatsInteractions.has(interaction)) {
                    this.etatsInteractions.set(
                        interaction,
                        interaction.disabled,
                    );
                }

                interaction.disabled = true;
            }

            return;
        }

        for (const [interaction, etaitDesactivee] of this.etatsInteractions) {
            if (interaction.isConnected) {
                interaction.disabled = etaitDesactivee;
            }
        }

        this.etatsInteractions.clear();
    }

    afficherErreur(message, proposerNouvelEssai = false) {
        this.masquerInformation();
        this.erreurTarget.textContent = message;
        this.erreurTarget.hidden = false;
        this.reessayerButtonTarget.hidden = !proposerNouvelEssai;
    }

    masquerErreur() {
        this.erreurTarget.textContent = '';
        this.erreurTarget.hidden = true;
        this.reessayerButtonTarget.hidden = true;
    }

    afficherInformation(message) {
        this.masquerErreur();
        this.informationTarget.textContent = message;
        this.informationTarget.hidden = false;
    }

    masquerInformation() {
        this.informationTarget.textContent = '';
        this.informationTarget.hidden = true;
    }
}

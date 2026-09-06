import { Controller } from '@hotwired/stimulus';
import {
    calculerApercuDegats,
    calculerMenaceFocus,
    capacitesTactiques,
    combattantsVivants,
    normaliserEtapesAnimation,
    puissanceGroupe,
} from './combat_v24_calculs.js';

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
        'pressionAttaque',
        'capacitesTactiques',
        'finCombat',
        'finCombatTitre',
        'finCombatMessage',
        'finCombatRecompense',
        'rapportFinalLink',
        'resultatRound',
        'resultatRoundNumero',
        'resultatRoundLignes',
        'resultatRoundPassifs',
        'historiqueRounds',
        'historiqueRoundsListe',
        'attenteAdversaire',
        'invitationCombat',
        'invitationCode',
        'visibiliteCombat',
        'participants',
        'preparationSection',
        'preparationMessage',
        'pretButton',
        'moiNom',
        'moiCombattants',
        'adversaireNom',
        'adversaireCombattants',
        'planSection',
        'planForm',
        'instructionPlan',
        'resumePlan',
        'planActionButton',
        'cibleAttaqueX',
        'cibleDefenseX',
        'cibleAttaqueY',
        'cibleDefenseY',
        'reinitialiserPlanButton',
        'envoyerPlanButton',
        'abandonButton',
        'annulerButton',
        'equipeSelect',
        'equipeApercu',
        'creerButton',
        'rechercherButton',
        'codeInvitationInput',
        'aucunHistoriqueCombat',
        'historiqueCombats',
    ];

    static values = {
        salonUrl: String,
        creerUrl: String,
        matchmakingUrl: String,
        rejoindreUrlModele: String,
        rejoindreParCodeUrl: String,
        combatUrlModele: String,
        planUrlModele: String,
        pretUrlModele: String,
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
        this.actionPlanActive = 'cibleAttaqueX';
        this.selectionPlanEnAttente = null;
        this.passifDetailsCompteur = 0;
        this.passifOuvert = this.lirePassifOuvert();
        this.etatsInteractions = new Map();
        this.animationRoundEnCours = false;
        this.generationAnimation = 0;
        this.generationGuidage = 0;
        this.numeroRoundGuide = null;
        this.signatureCombatAffiche = null;
        this.chargerSalon();
    }

    disconnect() {
        this.requeteEnCours?.abort();
        this.generationAnimation += 1;
        this.generationGuidage += 1;
        this.annulerActualisation();
    }

    rafraichir() {
        if (
            this.actionEnCours
            || this.animationRoundEnCours
        ) {
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

        this.effacerEtatPlan();
        this.combatActifIdCourant = null;
        this.combat = null;
        this.signatureCombatAffiche = null;
        this.effacerPassifOuvert();
        this.annulerActualisation();
        await this.chargerSalon();
    }

    changerEquipe() {
        this.afficherEquipeSelectionnee();
    }

    activerChoixPlan(event) {
        const cle = event.currentTarget?.dataset?.planCle;

        if (!this.clesPlan().includes(cle)) {
            return;
        }

        if (this.actionPlanActive !== cle) {
            this.selectionPlanEnAttente = null;
        }

        this.actionPlanActive = cle;
        this.fermerPassifIncompatible();
        this.mettreAJourPlanTactique();
    }

    synchroniserPlan() {
        this.selectionPlanEnAttente = null;

        if (!this.clesPlan().includes(this.actionPlanActive)) {
            this.actionPlanActive = 'cibleAttaqueX';
        }

        this.fermerPassifIncompatible();
        this.mettreAJourPlanTactique();
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

            await this.chargerCombat(combatId);
        });
    }

    reinitialiserPlanManuellement(event) {
        event?.preventDefault();

        if (
            this.actionEnCours
            || !this.planEstDisponible(this.combat)
        ) {
            return;
        }

        this.effacerEtatPlan();
        this.effacerPassifOuvert();
        this.selectionPlanEnAttente = null;

        for (const cle of this.clesPlan()) {
            const select = this.selectPlan(cle);

            if (select) {
                select.value = '';
            }
        }

        this.actionPlanActive = 'cibleAttaqueX';
        this.mettreAJourPlanTactique();
        this.afficherInformation('Tes choix ont été réinitialisés.');
    }

    async confirmerPret() {
        const combatId = this.combatActifIdCourant;

        if (combatId === null) {
            this.afficherErreur('Aucun combat actif n’est disponible.');

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.remplacerCombatId(
                    this.pretUrlModeleValue,
                    combatId,
                ),
                {},
                this.combat?.csrf?.pret,
            );

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

        const estRecherchePublique = this.combat?.prive !== true;
        const confirmation = window.confirm([
            estRecherchePublique
                ? 'Annuler cette recherche classée ?'
                : 'Annuler ce combat en attente ?',
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
            this.signatureCombatAffiche = null;
            await this.chargerSalon();
            this.afficherInformation(
                'Le combat en attente a bien été annulé.'
            );
        });
    }

    async creerCombatPrive() {
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
                {
                    equipeId,
                    prive: true,
                },
                this.salon?.csrf?.creer,
            );

            await this.chargerSalon();
        });
    }

    async rechercherAdversaire() {
        const equipeId = this.equipeSelectionneeId();

        if (equipeId === null) {
            this.afficherErreur(
                'Sélectionne une équipe avant de lancer la recherche.'
            );

            return;
        }

        await this.executerAction(async () => {
            const resultat = await this.envoyerJson(
                this.matchmakingUrlValue,
                { equipeId },
                this.salon?.csrf?.matchmaking,
            );

            await this.chargerSalon();

            if (resultat.etat === 'adversaire_trouve') {
                this.afficherInformation(
                    'Adversaire équilibré trouvé. Confirme ta préparation.'
                );
            }
        });
    }

    async relancerRechercheAdversaire() {
        const combatId = this.combatActifIdCourant;

        if (
            combatId === null
            || this.actionEnCours
            || this.combat?.statut !== 'en_attente'
            || this.combat?.matchmaking?.active !== true
            || this.combat?.adversaire !== null
        ) {
            return;
        }

        const equipeId = this.equipeSelectionneeId();

        if (equipeId === null) {
            await this.chargerCombat(combatId);

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.matchmakingUrlValue,
                { equipeId },
                this.salon?.csrf?.matchmaking,
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

    async rejoindreParCode(event) {
        event.preventDefault();

        const equipeId = this.equipeSelectionneeId();
        const code = this.codeInvitationInputTarget.value
            .trim()
            .toUpperCase();

        if (equipeId === null) {
            this.afficherErreur(
                'Sélectionne une équipe avant de rejoindre un combat.'
            );

            return;
        }

        if (!/^SV-[A-F0-9]{6}$/.test(code)) {
            this.afficherErreur(
                'Le code doit respecter le format SV-XXXXXX.'
            );

            return;
        }

        await this.executerAction(async () => {
            await this.envoyerJson(
                this.rejoindreParCodeUrlValue,
                { equipeId, code },
                this.salon?.csrf?.rejoindre,
            );

            this.codeInvitationInputTarget.value = '';
            await this.chargerSalon();
        });
    }

    async copierCodeInvitation() {
        const code = this.combat?.codeInvitation;

        if (typeof code !== 'string' || code === '') {
            this.afficherErreur('Aucun code d’invitation n’est disponible.');

            return;
        }

        try {
            await window.navigator.clipboard.writeText(code);
            this.afficherInformation(`Code ${code} copié.`);
        } catch {
            this.afficherErreur(
                `Impossible de copier automatiquement. Code : ${code}`
            );
        }
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
        this.signatureCombatAffiche = null;
        this.effacerEtatPlan();
        this.annulerActualisation();
        this.combatActifTarget.hidden = true;
        this.salonTarget.hidden = false;
        this.afficherEquipes();
        this.afficherHistoriqueCombats();
    }

    async chargerCombat(combatId) {
        const etatPlanAvantActualisation =
            this.sauvegarderEtatPlan() ?? this.lireEtatPlan(combatId);
        // Une cible choisie avec un seul clic reste locale : un refresh ne
        // doit jamais la transformer en choix confirmé.
        this.selectionPlanEnAttente = null;

        this.annulerActualisation();
        this.requeteEnCours?.abort();

        const requete = new AbortController();
        const premierChargement = this.combat === null;
        this.requeteEnCours = requete;
        this.chargementTarget.textContent = 'Actualisation du combat…';
        this.chargementTarget.hidden = !premierChargement;
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
                this.signatureCombatAffiche = null;
                await this.chargerSalon();
                this.afficherInformation([
                    'Le combat a été annulé automatiquement',
                    'après 5 minutes sans adversaire.',
                ].join(' '));

                return;
            }

            if (donnees.annulationPreparationAutomatique === true) {
                this.combatActifIdCourant = null;
                this.combat = null;
                this.signatureCombatAffiche = null;
                await this.chargerSalon();
                this.afficherInformation([
                    'Le combat a été annulé automatiquement',
                    'car aucun joueur n’a confirmé sa préparation',
                    'dans les 5 minutes.',
                ].join(' '));

                return;
            }

            const planDisponible = this.planEstDisponible(donnees);
            const planPrecedentDisponible = this.planEstDisponible(
                this.combat,
            );
            const numeroRound = this.entierPositif(donnees.numeroRound);
            const numeroRoundPrecedent = this.entierPositif(
                this.combat?.numeroRound,
            );
            const nouveauTour = planDisponible
                && (
                    !planPrecedentDisponible
                    || numeroRound !== numeroRoundPrecedent
                );

            if (nouveauTour) {
                this.reinitialiserPlanPourNouveauTour();
            }

            const signature = JSON.stringify(donnees);
            const combatModifie = signature !== this.signatureCombatAffiche;

            this.combat = donnees;

            if (combatModifie) {
                this.signatureCombatAffiche = signature;
                this.afficherCombat();
            } else {
                this.afficherPreparation();
                this.afficherEtatRound();
            }

            if (planDisponible) {
                if (!nouveauTour || premierChargement) {
                    this.restaurerEtatPlan(
                        etatPlanAvantActualisation,
                        donnees,
                    );
                } else {
                    this.effacerEtatPlan(combatId);
                }
                this.restaurerPassifOuvert();
            } else {
                this.effacerEtatPlan(combatId);
            }

            if (donnees.forfaitPreparationAutomatique === true) {
                this.afficherInformation([
                    'La préparation est terminée par forfait',
                    'après 5 minutes sans seconde confirmation.',
                ].join(' '));
            }

            await this.animerNouveauRoundSiNecessaire();
            await this.animerGuidageNouveauTourSiNecessaire(nouveauTour);
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
        this.sauvegarderPassifOuvert();

        const statuts = {
            en_attente: 'En attente',
            en_cours: 'En cours',
            termine: 'Terminé',
            abandonne: 'Abandonné',
            forfait: 'Terminé par forfait',
            annule: 'Annulé',
        };

        this.combatStatutTarget.textContent = statuts[this.combat.statut]
            ?? this.combat.statut
            ?? 'Inconnu';
        const numeroRound = this.combat.numeroRound ?? '—';
        const limiteRounds = this.combat.limiteRounds;
        this.numeroRoundTarget.textContent = Number.isInteger(limiteRounds)
            ? `${numeroRound} / ${limiteRounds}`
            : String(numeroRound);

        this.afficherParticipant(
            this.combat.moi,
            this.moiNomTarget,
            this.moiCombattantsTarget,
            'Ton équipe',
            'moi',
        );

        const adversairePresent = this.combat.adversaire !== null;
        this.attenteAdversaireTarget.hidden = adversairePresent;
        const matchmaking = this.combat?.matchmaking;

        if (!adversairePresent && matchmaking?.active === true) {
            this.attenteAdversaireTarget.textContent = [
                'Recherche classée en cours.',
                `ELO ${matchmaking.elo ?? '—'},`,
                `puissance ${matchmaking.puissanceEquipe ?? '—'}.`,
                'Écarts acceptés :',
                `${matchmaking.ecartEloMaximum ?? '—'} ELO et`,
                `${matchmaking.ecartPuissanceMaximumPourcent ?? '—'} % de puissance.`,
            ].join(' ');
        } else if (!adversairePresent) {
            this.attenteAdversaireTarget.textContent =
                'En attente d’un adversaire…';
        }
        const codeInvitation = typeof this.combat.codeInvitation === 'string'
            ? this.combat.codeInvitation
            : '';
        const afficherInvitation = this.combat.statut === 'en_attente'
            && !adversairePresent
            && this.combat?.prive === true
            && codeInvitation !== '';

        this.invitationCombatTarget.hidden = !afficherInvitation;
        this.invitationCodeTarget.textContent = afficherInvitation
            ? codeInvitation
            : '';

        this.visibiliteCombatTarget.textContent = this.combat?.prive === true
            ? 'Combat privé : seul un joueur possédant ce code peut le rejoindre.'
            : 'Combat public : il est également visible dans la liste du salon.';

        this.afficherParticipant(
            this.combat.adversaire,
            this.adversaireNomTarget,
            this.adversaireCombattantsTarget,
            'Équipe adverse',
            'adversaire',
        );

        this.afficherCapacitesTactiques();
        this.afficherMenacesFocus();

        this.afficherPreparation();
        this.afficherEtatRound();
        this.afficherPressionAttaque();
        this.afficherFinCombat();
        this.afficherDernierRound();
        this.afficherHistoriqueRounds();
        this.afficherFormulairePlan();
        this.afficherBoutonAbandon();
        this.afficherBoutonAnnulation();
    }

    afficherPreparation() {
        const preparation = this.combat?.preparation;
        const active = preparation?.active === true;

        this.preparationSectionTarget.hidden = !active;

        if (!active) {
            return;
        }

        const moiPret = preparation.moiPret === true;
        const adversairePret = preparation.adversairePret === true;

        if (moiPret && !adversairePret) {
            this.preparationMessageTarget.textContent =
                'Tu es prêt. En attente de la confirmation adverse.'
                + this.texteDelaiPreparation(
                    ' Victoire par forfait possible dans '
                );
        } else if (!moiPret && adversairePret) {
            this.preparationMessageTarget.textContent =
                'Ton adversaire est prêt. Confirme quand ton équipe est prête.'
                + this.texteDelaiPreparation(
                    ' Tu seras déclaré forfait dans '
                );
        } else {
            this.preparationMessageTarget.textContent =
                'Vérifie ton équipe puis confirme ta préparation.'
                + this.texteDelaiPreparation(
                    ' Annulation automatique dans '
                );
        }

        this.pretButtonTarget.disabled = moiPret;
        this.pretButtonTarget.textContent = moiPret
            ? 'Prêt confirmé'
            : 'Je suis prêt';
    }

    afficherBoutonAbandon() {
        this.abandonButtonTarget.hidden = !(
            this.combat.statut === 'en_cours'
            && this.combat.adversaire !== null
        );
    }

    afficherBoutonAnnulation() {
        this.annulerButtonTarget.textContent = this.combat?.prive === true
            ? 'Annuler ce combat'
            : 'Annuler la recherche';
        this.annulerButtonTarget.hidden = !(
            this.combat.statut === 'en_attente'
            && this.combat.adversaire === null
        );
    }

    afficherFinCombat() {
        const estTermine = this.combat.statut === 'termine'
            || this.combat.statut === 'abandonne'
            || this.combat.statut === 'forfait'
            || this.combat.statut === 'annule';

        if (!estTermine) {
            this.finCombatTarget.hidden = true;
            this.finCombatTarget.dataset.resultat = '';
            this.finCombatRecompenseTarget.hidden = true;
            this.finCombatRecompenseTarget.textContent = '';
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
        } else if (this.combat.statut === 'forfait') {
            if (victoire) {
                titre = 'Victoire par forfait';
                message = 'Ton adversaire n’a pas envoyé son plan à temps.';
                resultat = 'victoire';
            } else {
                titre = 'Défaite par forfait';
                message = 'Le délai pour envoyer ton plan est dépassé.';
                resultat = 'defaite';
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
        const recompensePieces = Number(this.combat.recompensePieces ?? 0);
        this.finCombatRecompenseTarget.textContent =
            recompensePieces > 0
                ? `Récompense : ${recompensePieces} pièce${recompensePieces > 1 ? 's' : ''}.`
                : 'Aucune pièce gagnée.';
        this.finCombatRecompenseTarget.hidden = false;
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
        this.resultatRoundPassifsTarget.hidden = true;
        this.resultatRoundPassifsTarget.textContent = '';

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

        const passifs = Object.values(resultats)
            .flatMap((resultat) => Array.isArray(resultat?.passifsActifs)
                ? resultat.passifsActifs
                : [])
            .filter((passif, index, liste) => {
                const cle = `${passif?.nom ?? ''}|${passif?.type ?? ''}|${passif?.valeur ?? ''}`;

                return liste.findIndex((element) =>
                    `${element?.nom ?? ''}|${element?.type ?? ''}|${element?.valeur ?? ''}` === cle
                ) === index;
            });

        if (passifs.length > 0) {
            this.resultatRoundPassifsTarget.textContent = [
                'Passifs actifs :',
                ...passifs.map((passif) => {
                    const estPenetration = [
                        'precision',
                        'perforation_i',
                        'perforation_ii',
                        'precision_spectrale',
                    ].includes(passif?.type);
                    const estMalus = passif?.direction === 'malus';
                    const valeur = Number.isFinite(Number(passif?.valeur))
                        ? ` (${estPenetration || estMalus ? '-' : '+'}${passif.valeur} %${estPenetration ? ' DEF' : ''})`
                        : '';
                    const description = typeof passif?.description === 'string'
                        && passif.description.trim() !== ''
                        ? ` — ${passif.description.trim()}`
                        : '';

                    return `${passif?.nom ?? 'Passif'}${valeur}${description}`;
                }),
            ].join(' ');
            this.resultatRoundPassifsTarget.hidden = false;
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

    planEstDisponible(combat) {
        return combat?.statut === 'en_cours'
            && combat?.adversaire !== null
            && combat?.adversaire !== undefined
            && combat?.preparation?.active !== true
            && combat?.planSoumis === false;
    }

    cleEtatPlan(combatId = this.combatActifIdCourant) {
        const id = this.entierPositif(combatId);

        return id === null
            ? null
            : `stickverse.combat.${id}.plan-brouillon`;
    }

    lireEtatPlan(combatId = this.combatActifIdCourant) {
        const cle = this.cleEtatPlan(combatId);

        if (cle === null) {
            return null;
        }

        try {
            const valeur = JSON.parse(
                window.sessionStorage.getItem(cle) ?? 'null'
            );
            const id = this.entierPositif(valeur?.combatId);
            const round = this.entierPositif(valeur?.numeroRound);

            if (
                id === null
                || round === null
                || id !== this.entierPositif(combatId)
                || !this.clesPlan().every(
                    (planCle) => typeof valeur?.selections?.[planCle]
                        === 'string'
                )
                || !this.clesPlan().includes(valeur?.actionPlanActive)
            ) {
                return null;
            }

            return {
                combatId: id,
                numeroRound: round,
                actionPlanActive: valeur.actionPlanActive,
                selections: Object.fromEntries(
                    this.clesPlan().map((planCle) => [
                        planCle,
                        valeur.selections[planCle],
                    ])
                ),
            };
        } catch {
            return null;
        }
    }

    sauvegarderEtatPlan() {
        if (
            !this.combat
            || !this.planEstDisponible(this.combat)
            || this.cleEtatPlan() === null
        ) {
            return null;
        }

        const etat = {
            combatId: this.combatActifIdCourant,
            numeroRound: this.entierPositif(this.combat.numeroRound),
            actionPlanActive: this.actionPlanActive,
            selections: Object.fromEntries(
                this.clesPlan().map((cle) => [
                    cle,
                    this.selectPlan(cle)?.value ?? '',
                ])
            ),
        };

        if (etat.numeroRound === null) {
            return etat;
        }

        try {
            window.sessionStorage.setItem(
                this.cleEtatPlan(),
                JSON.stringify(etat),
            );
        } catch {
            // L’état retourné reste utilisable pendant cette actualisation.
        }

        return etat;
    }

    effacerEtatPlan(combatId = this.combatActifIdCourant) {
        const cle = this.cleEtatPlan(combatId);

        if (cle === null) {
            return;
        }

        try {
            window.sessionStorage.removeItem(cle);
        } catch {
            // Rien à faire : la session active est déjà prioritaire.
        }
    }

    restaurerEtatPlan(etat, combat) {
        const combatId = this.entierPositif(combat?.combatId);
        const numeroRound = this.entierPositif(combat?.numeroRound);

        if (
            !etat
            || combatId === null
            || numeroRound === null
            || etat.combatId !== combatId
            || etat.numeroRound !== numeroRound
            || !this.planEstDisponible(combat)
        ) {
            return;
        }

        this.selectionPlanEnAttente = null;

        for (const cle of this.clesPlan()) {
            const select = this.selectPlan(cle);
            const valeur = etat.selections?.[cle] ?? '';
            const optionValide = Array.from(select?.options ?? [])
                .some((option) => option.value === valeur);

            if (select && (valeur === '' || optionValide)) {
                select.value = valeur;
            }
        }

        if (this.clesPlan().includes(etat.actionPlanActive)) {
            this.actionPlanActive = etat.actionPlanActive;
        }

        this.fermerPassifIncompatible();
        this.mettreAJourPlanTactique();
    }

    reinitialiserPlanPourNouveauTour() {
        this.effacerEtatPlan();
        this.effacerPassifOuvert();
        this.selectionPlanEnAttente = null;

        for (const cle of this.clesPlan()) {
            const select = this.selectPlan(cle);

            if (select) {
                select.value = '';
            }
        }

        this.actionPlanActive = 'cibleAttaqueX';
        this.mettreAJourPlanTactique();
    }

    async animerGuidageNouveauTourSiNecessaire(nouveauTour) {
        const numeroRound = this.entierPositif(this.combat?.numeroRound);

        if (
            !nouveauTour
            || numeroRound === null
            || this.numeroRoundGuide === numeroRound
            || !this.planEstDisponible(this.combat)
        ) {
            return;
        }

        this.numeroRoundGuide = numeroRound;
        const generation = ++this.generationGuidage;
        const mouvementReduit = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
        ).matches;
        const dureeIntroduction = mouvementReduit ? 180 : 680;

        // Le premier choix reste toujours ATK X. L'animation accompagne ce
        // choix sans bloquer les clics ni basculer artificiellement en DEF.
        this.actionPlanActive = 'cibleAttaqueX';
        this.mettreAJourPlanTactique();
        this.instructionPlanTarget.textContent =
            'Étape 1/2 — Choisis les deux cartes adverses à attaquer.';

        try {
            await this.attendreGuidage(dureeIntroduction, generation);
        } finally {
            if (generation !== this.generationGuidage) {
                return;
            }
        }
    }

    attendreGuidage(duree, generation) {
        return new Promise((resolve) => {
            window.setTimeout(() => {
                resolve(generation === this.generationGuidage);
            }, duree);
        });
    }

    afficherFormulairePlan() {
        const peutJouer = this.planEstDisponible(this.combat);

        this.planSectionTarget.hidden = !peutJouer;

        if (!peutJouer) {
            this.selectionPlanEnAttente = null;
            delete this.combatActifTarget.dataset.guidagePhase;

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

        if (!this.clesPlan().includes(this.actionPlanActive)) {
            this.actionPlanActive = 'cibleAttaqueX';
        }

        this.mettreAJourPlanTactique();
    }

    remplirCibleUnique(select, combattants) {
        if (combattants.length === 1) {
            select.value = combattants[0].slot;
        }
    }

    combattantsVivants(participant) {
        return combattantsVivants(participant);
    }

    remplirSelectCibles(select, combattants) {
        const valeurPrecedente = select.value;
        select.replaceChildren();

        const invitation = document.createElement('option');
        invitation.value = '';
        invitation.textContent = 'Choisir…';
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

    clesPlan() {
        return [
            'cibleAttaqueX',
            'cibleAttaqueY',
            'cibleDefenseX',
            'cibleDefenseY',
        ];
    }

    selectPlan(cle) {
        const selects = {
            cibleAttaqueX: this.cibleAttaqueXTarget,
            cibleAttaqueY: this.cibleAttaqueYTarget,
            cibleDefenseX: this.cibleDefenseXTarget,
            cibleDefenseY: this.cibleDefenseYTarget,
        };

        return selects[cle] ?? null;
    }

    campPourActionPlan(cle) {
        return cle.startsWith('cibleAttaque')
            ? 'adversaire'
            : 'moi';
    }

    synchroniserPhaseGuidage() {
        if (
            this.planSectionTarget.hidden
            || !this.planEstDisponible(this.combat)
        ) {
            delete this.combatActifTarget.dataset.guidagePhase;

            return;
        }

        // Conserver l'action explicitement choisie permet de modifier une
        // cible déjà renseignée (même si une autre case reste à compléter).
        const prochaineAction = this.clesPlan().includes(this.actionPlanActive)
            ? this.actionPlanActive
            : this.clesPlan().find(
                (cle) => this.selectPlan(cle)?.value === '',
            );

        if (!prochaineAction) {
            delete this.combatActifTarget.dataset.guidagePhase;

            return;
        }

        this.combatActifTarget.dataset.guidagePhase =
            prochaineAction.startsWith('cibleAttaque')
                ? 'attaque'
                : 'defense';
    }

    selectionnerCiblePlan(camp, slot) {
        if (
            this.planSectionTarget.hidden
            || this.planEstComplet()
            || camp !== this.campPourActionPlan(this.actionPlanActive)
        ) {
            return;
        }

        const select = this.selectPlan(this.actionPlanActive);
        const optionValide = Array.from(select?.options ?? [])
            .some((option) => option.value === slot && !option.disabled);

        if (!select || !optionValide) {
            return;
        }

        const selectionEnAttente = this.selectionPlanEnAttente;

        if (
            selectionEnAttente?.cle !== this.actionPlanActive
            || selectionEnAttente.slot !== slot
        ) {
            this.selectionPlanEnAttente = {
                cle: this.actionPlanActive,
                slot,
            };
            this.mettreAJourPlanTactique();

            return;
        }

        select.value = slot;
        this.selectionPlanEnAttente = null;
        this.actionPlanActive = this.prochaineActionPlan(
            this.actionPlanActive,
            true,
        );
        this.fermerPassifIncompatible();
        this.mettreAJourPlanTactique();
    }

    prochaineActionPlan(cleActuelle, chercherVide = false) {
        const cles = this.clesPlan();
        const depart = Math.max(0, cles.indexOf(cleActuelle));

        for (let decalage = 1; decalage <= cles.length; decalage += 1) {
            const cle = cles[(depart + decalage) % cles.length];

            if (!chercherVide || this.selectPlan(cle)?.value === '') {
                return cle;
            }
        }

        return cleActuelle;
    }

    mettreAJourPlanTactique() {
        if (!this.hasPlanActionButtonTarget) {
            return;
        }

        const planComplet = this.planEstComplet();
        const libelles = {
            cibleAttaqueX: 'ATTAQUES — Sélectionne la cible de ATK X.',
            cibleAttaqueY: 'ATTAQUES — Sélectionne la cible de ATK Y.',
            cibleDefenseX: 'DÉFENSES — Sélectionne l’allié de DEF X.',
            cibleDefenseY: 'DÉFENSES — Sélectionne l’allié de DEF Y.',
        };

        this.planSectionTarget.classList.toggle('plan-complet', planComplet);

        for (const bouton of this.planActionButtonTargets) {
            const cle = bouton.dataset.planCle;
            const select = this.selectPlan(cle);
            const valeur = bouton.querySelector('[data-plan-valeur]');
            const option = Array.from(select?.options ?? [])
                .find((element) => element.value === select.value);

            bouton.classList.toggle(
                'est-active',
                !planComplet && cle === this.actionPlanActive,
            );
            bouton.classList.toggle(
                'est-complete',
                select?.value !== '',
            );

            if (valeur) {
                valeur.textContent = option?.textContent ?? 'À choisir';
            }
        }

        const selectionEnAttente = this.selectionPlanEnAttente;
        this.instructionPlanTarget.textContent = planComplet
            ? 'Plan complet — vérifie les attaques et les protections avant de lancer le tour.'
            : selectionEnAttente?.cle === this.actionPlanActive
                ? 'Cible en attente — clique une seconde fois sur la même carte pour confirmer.'
                : libelles[this.actionPlanActive] ?? 'Choisis une cible.';
        this.envoyerPlanButtonTarget.disabled = this.clesPlan().some(
            (cle) => this.selectPlan(cle)?.value === ''
        );
        this.mettreAJourCartesPlan();
        this.mettreAJourResumePlan();
        this.synchroniserPhaseGuidage();
        this.actualiserApercusSurvoles();
    }

    planEstComplet() {
        return this.clesPlan().every(
            (cle) => Boolean(this.selectPlan(cle)?.value)
        );
    }

    mettreAJourCartesPlan() {
        const cartes = this.participantsTarget.querySelectorAll(
            '.carte-combattant[data-slot][data-camp]'
        );
        const planComplet = this.planEstComplet();
        const selectionEnAttente = this.selectionPlanEnAttente;
        const campActif = this.campPourActionPlan(this.actionPlanActive);
        const choixParCarte = new Map();
        const libelles = {
            cibleAttaqueX: 'ATQ X',
            cibleAttaqueY: 'ATQ Y',
            cibleDefenseX: 'DÉF X',
            cibleDefenseY: 'DÉF Y',
        };

        for (const cle of this.clesPlan()) {
            const slot = this.selectPlan(cle)?.value;

            if (!slot) {
                continue;
            }

            const identifiant = `${this.campPourActionPlan(cle)}:${slot}`;
            const choix = choixParCarte.get(identifiant) ?? [];
            choix.push(libelles[cle]);
            choixParCarte.set(identifiant, choix);
        }

        for (const carte of cartes) {
            const estVivante = carte.dataset.vivant !== 'false';
            const estSelectionnable = !planComplet
                && estVivante
                && carte.dataset.camp === campActif;
            const identifiant = `${carte.dataset.camp}:${carte.dataset.slot}`;
            const choix = choixParCarte.get(identifiant) ?? [];
            const estEnAttente = selectionEnAttente?.cle
                === this.actionPlanActive
                && selectionEnAttente.slot === carte.dataset.slot
                && carte.dataset.camp === campActif;
            const conteneur = carte.querySelector(
                '.carte-combattant-choix'
            );

            carte.classList.toggle(
                'est-selectionnable',
                estSelectionnable,
            );
            carte.classList.toggle('est-choisie', choix.length > 0);
            carte.classList.toggle('est-en-attente', estEnAttente);
            carte.tabIndex = estSelectionnable ? 0 : -1;

            if (estSelectionnable) {
                carte.setAttribute('role', 'button');
            } else {
                carte.removeAttribute('role');
            }

            carte.setAttribute(
                'aria-label',
                estSelectionnable
                    ? estEnAttente
                        ? `Confirmer ${carte.dataset.slot} pour ${this.actionPlanActive}`
                        : `Choisir ${carte.dataset.slot} pour ${this.actionPlanActive}`
                    : carte.textContent.trim(),
            );

            if (conteneur) {
                conteneur.replaceChildren();

                for (const choixTexte of choix) {
                    const badge = document.createElement('span');
                    badge.textContent = choixTexte;
                    badge.dataset.type = choixTexte.startsWith('ATQ')
                        ? 'attaque'
                        : 'defense';
                    badge.title = badge.dataset.type === 'attaque'
                        ? 'Cible d’attaque'
                        : 'Allié protégé';
                    conteneur.append(badge);
                }
            }
        }
    }

    mettreAJourResumePlan() {
        const selections = Object.fromEntries(
            this.clesPlan().map((cle) => [
                cle,
                this.selectPlan(cle)?.value ?? '',
            ])
        );
        const incomplet = Object.values(selections)
            .some((valeur) => valeur === '');

        if (incomplet) {
            this.resumePlanTarget.textContent =
                'Complète les quatre choix pour afficher le résumé du plan.';

            return;
        }

        const attaqueX = this.puissanceGroupe('X', 'attaque');
        const attaqueY = this.puissanceGroupe('Y', 'attaque');
        const defenseX = this.puissanceGroupe('X', 'defense');
        const defenseY = this.puissanceGroupe('Y', 'defense');
        const attaque = selections.cibleAttaqueX
            === selections.cibleAttaqueY
            ? `Focus sur ${selections.cibleAttaqueX} : ${attaqueX + attaqueY} ATQ.`
            : `Split : X vise ${selections.cibleAttaqueX} (${attaqueX} ATQ), Y vise ${selections.cibleAttaqueY} (${attaqueY} ATQ).`;
        const defense = selections.cibleDefenseX
            === selections.cibleDefenseY
            ? `Double défense sur ${selections.cibleDefenseX} : ${defenseX + defenseY} DÉF.`
            : `Défense : X protège ${selections.cibleDefenseX} (${defenseX}), Y protège ${selections.cibleDefenseY} (${defenseY}).`;

        this.resumePlanTarget.textContent = [
            `⚔ Attaques : ${attaque.replace(/^Focus sur |^Split : /, '')}`,
            `🛡 Protections : ${defense.replace(/^Double défense sur |^Défense : /, '')}`,
        ].join('  ');
    }

    puissanceGroupe(groupe, statistique) {
        const bonus = Number(
            this.combat?.pressionAttaque?.bonusPourcentage ?? 0
        );

        return puissanceGroupe(
            this.combat?.moi,
            groupe,
            statistique,
            bonus,
        );
    }

    afficherCapacitesTactiques() {
        if (!this.hasCapacitesTactiquesTarget) {
            return;
        }

        const bonus = Number(
            this.combat?.pressionAttaque?.bonusPourcentage ?? 0
        );
        const equipes = [
            ['Toi', capacitesTactiques(this.combat?.moi, bonus)],
            [
                'Adversaire',
                capacitesTactiques(this.combat?.adversaire, bonus),
            ],
        ];

        this.capacitesTactiquesTarget.replaceChildren();

        for (const [nom, valeurs] of equipes) {
            const groupe = document.createElement('section');
            const titre = document.createElement('h4');
            const grille = document.createElement('div');

            groupe.className = 'capacites-equipe';
            titre.textContent = nom;
            grille.className = 'capacites-equipe-grille';

            for (const [libelle, valeur, type] of [
                ['ATQ X', valeurs.attaqueX, 'attaque'],
                ['ATQ Y', valeurs.attaqueY, 'attaque'],
                ['Focus', valeurs.focus, 'attaque'],
                ['DÉF X', valeurs.defenseX, 'defense'],
                ['DÉF Y', valeurs.defenseY, 'defense'],
                ['Double', valeurs.doubleDefense, 'defense'],
            ]) {
                const statistique = document.createElement('span');
                const nomStatistique = document.createElement('small');
                const valeurStatistique = document.createElement('strong');

                statistique.dataset.type = type;
                nomStatistique.textContent = libelle;
                valeurStatistique.textContent = String(valeur);
                statistique.append(nomStatistique, valeurStatistique);
                grille.append(statistique);
            }

            groupe.append(titre, grille);
            this.capacitesTactiquesTarget.append(groupe);
        }
    }

    afficherMenacesFocus() {
        const bonus = Number(
            this.combat?.pressionAttaque?.bonusPourcentage ?? 0
        );
        const configurations = [
            ['moi', this.combat?.moi, this.combat?.adversaire],
            ['adversaire', this.combat?.adversaire, this.combat?.moi],
        ];

        for (const [camp, defenseur, attaquant] of configurations) {
            for (const combattant of defenseur?.combattants ?? []) {
                const carte = this.carteCombattant(camp, combattant.slot);
                const badge = carte?.querySelector(
                    '.carte-combattant-menace'
                );
                const menace = calculerMenaceFocus(
                    combattant,
                    attaquant,
                    defenseur,
                    bonus,
                );

                if (!badge || !carte) {
                    continue;
                }

                carte.dataset.menace = menace?.niveau ?? '';
                badge.hidden = menace === null;
                badge.replaceChildren();

                if (menace) {
                    const symbole = document.createElement('span');
                    const texte = document.createElement('span');

                    symbole.className = 'carte-combattant-menace-symbole';
                    texte.className = 'carte-combattant-menace-texte';
                    symbole.textContent = menace.symbole;
                    texte.textContent = menace.texte;
                    badge.append(symbole, document.createTextNode(' '), texte);
                    badge.title = menace.texte;
                    badge.setAttribute(
                        'aria-label',
                        `Avertissement : ${menace.texte}`,
                    );
                } else {
                    badge.removeAttribute('title');
                    badge.removeAttribute('aria-label');
                }
            }
        }
    }

    afficherParticipant(
        participant,
        nomTarget,
        listeTarget,
        libelle,
        camp,
    ) {
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

        nomTarget.textContent = participant.pseudo ?? libelle;

        const combattants = Array.isArray(participant.combattants)
            ? participant.combattants
            : [];

        for (const combattant of combattants) {
            const carte = this.creerCarteCombattant(
                combattant,
                pourcentagesPrecedents.get(combattant.slot),
            );

            carte.dataset.camp = camp;
            carte.addEventListener('click', () => {
                this.selectionnerCiblePlan(camp, combattant.slot);
            });
            carte.addEventListener('mouseenter', () => {
                this.afficherApercuCible(carte, camp, combattant.slot);
            });
            carte.addEventListener('mouseleave', () => {
                this.masquerApercuCible(carte);
            });
            carte.addEventListener('focusin', () => {
                this.afficherApercuCible(carte, camp, combattant.slot);
            });
            carte.addEventListener('focusout', () => {
                this.masquerApercuCible(carte);
            });
            carte.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    this.selectionnerCiblePlan(camp, combattant.slot);
                }
            });
            listeTarget.append(carte);
        }
    }

    afficherApercuCible(carte, camp, slot) {
        if (
            this.planEstComplet()
            || this.carteEstCiblePlan(carte)
        ) {
            return;
        }

        if (
            camp === 'adversaire'
            && this.actionPlanActive.startsWith('cibleAttaque')
        ) {
            this.afficherApercuDegats(carte, camp, slot);

            return;
        }

        if (
            camp === 'moi'
            && this.actionPlanActive.startsWith('cibleDefense')
        ) {
            this.afficherApercuDefense(carte, camp, slot);
        }
    }

    afficherApercuDegats(carte, camp, slot, inclureCibleActive = true) {
        if (
            camp !== 'adversaire'
            || this.planSectionTarget.hidden
            || carte.dataset.vivant === 'false'
        ) {
            return;
        }

        let attaque = 0;

        for (const groupe of ['X', 'Y']) {
            const cle = groupe === 'X'
                ? 'cibleAttaqueX'
                : 'cibleAttaqueY';
            const cible = this.selectPlan(cle)?.value;

            if (
                cible === slot
                || (
                    inclureCibleActive
                    && cle === this.actionPlanActive
                    && this.actionPlanActive.startsWith('cibleAttaque')
                )
            ) {
                attaque += this.puissanceGroupe(groupe, 'attaque');
            }
        }

        if (attaque === 0) {
            return;
        }

        const apercu = calculerApercuDegats(
            attaque,
            carte.dataset.pvActuels,
            carte.dataset.pvMaximum,
        );
        const bande = carte.querySelector(
            '.carte-combattant-vie-preview'
        );
        const previsualisation = carte.querySelector(
            '.carte-combattant-preview-degats'
        );

        if (!bande || !previsualisation) {
            return;
        }

        const pourcentageActuel = Number(carte.dataset.pourcentageVie ?? 0);
        bande.dataset.mode = 'degats';
        bande.style.left = `${apercu.pourcentageRestant}%`;
        bande.style.width = `${Math.max(
            0,
            pourcentageActuel - apercu.pourcentageRestant,
        )}%`;
        bande.hidden = apercu.degats === 0;
        previsualisation.textContent = apercu.degats > 0
            ? `−${apercu.degats} PV sans défense`
            : '0 dégât sans défense';
        previsualisation.hidden = false;
        carte.classList.add('affiche-preview-degats');
    }

    afficherApercuDefense(carte, camp, slot, inclureCibleActive = true) {
        if (
            camp !== 'moi'
            || this.planSectionTarget.hidden
            || carte.dataset.vivant === 'false'
        ) {
            return;
        }

        let defense = 0;

        for (const groupe of ['X', 'Y']) {
            const cle = groupe === 'X'
                ? 'cibleDefenseX'
                : 'cibleDefenseY';
            const cible = this.selectPlan(cle)?.value;

            if (
                cible === slot
                || (
                    inclureCibleActive
                    && cle === this.actionPlanActive
                    && this.actionPlanActive.startsWith('cibleDefense')
                )
            ) {
                defense += this.puissanceGroupe(groupe, 'defense');
            }
        }

        if (defense <= 0) {
            return;
        }

        const bande = carte.querySelector(
            '.carte-combattant-vie-preview'
        );
        const previsualisation = carte.querySelector(
            '.carte-combattant-preview-degats'
        );

        if (!bande || !previsualisation) {
            return;
        }

        const pvMaximum = Number(carte.dataset.pvMaximum ?? 0);
        const pourcentageActuel = Number(carte.dataset.pourcentageVie ?? 0);
        const pourcentageDefense = pvMaximum > 0
            ? Math.min(100, (defense / pvMaximum) * 100)
            : 0;
        const largeur = Math.min(pourcentageActuel, pourcentageDefense);

        bande.dataset.mode = 'defense';
        bande.style.left = `${Math.max(0, pourcentageActuel - largeur)}%`;
        bande.style.width = `${largeur}%`;
        bande.hidden = largeur <= 0;
        previsualisation.textContent = `+${defense} DÉF sur cette carte`;
        previsualisation.hidden = false;
        carte.classList.add('affiche-preview-defense');
    }

    masquerApercuDegats(carte) {
        const bande = carte.querySelector(
            '.carte-combattant-vie-preview'
        );
        const previsualisation = carte.querySelector(
            '.carte-combattant-preview-degats'
        );

        if (bande) {
            bande.hidden = true;
            bande.style.left = '0';
            bande.style.width = '0';
            delete bande.dataset.mode;
        }

        if (previsualisation) {
            previsualisation.hidden = true;
        }

        carte.classList.remove('affiche-preview-degats');
        carte.classList.remove('affiche-preview-defense');
    }

    masquerApercuCible(carte) {
        if (
            this.planEstComplet()
            || this.carteEstCiblePlan(carte)
        ) {
            return;
        }

        this.masquerApercuDegats(carte);
    }

    actualiserApercusSurvoles() {
        this.actualiserApercusPlan();

        if (this.planEstComplet()) {
            return;
        }

        for (const carte of this.participantsTarget.querySelectorAll(
            '.carte-combattant:hover[data-camp="adversaire"]'
        )) {
            this.afficherApercuCible(
                carte,
                carte.dataset.camp,
                carte.dataset.slot,
            );
        }

        for (const carte of this.participantsTarget.querySelectorAll(
            '.carte-combattant:hover[data-camp="moi"]'
        )) {
            this.afficherApercuCible(
                carte,
                carte.dataset.camp,
                carte.dataset.slot,
            );
        }
    }

    actualiserApercusPlan() {
        for (const carte of this.participantsTarget.querySelectorAll(
            '.carte-combattant.affiche-preview-degats, '
            + '.carte-combattant.affiche-preview-defense'
        )) {
            this.masquerApercuDegats(carte);
        }

        for (const cle of this.clesPlan()) {
            const slot = this.selectPlan(cle)?.value;

            if (!slot) {
                continue;
            }

            const camp = this.campPourActionPlan(cle);
            const carte = this.carteCombattant(camp, slot);

            if (!carte) {
                continue;
            }

            if (cle.startsWith('cibleAttaque')) {
                this.afficherApercuDegats(carte, camp, slot, false);
            } else {
                this.afficherApercuDefense(carte, camp, slot, false);
            }
        }
    }

    carteEstCiblePlan(carte) {
        if (!carte?.dataset?.camp || !carte?.dataset?.slot) {
            return false;
        }

        return this.clesPlan().some((cle) =>
            this.campPourActionPlan(cle) === carte.dataset.camp
            && this.selectPlan(cle)?.value === carte.dataset.slot
        );
    }

    afficherPressionAttaque() {
        const bonus = Number.parseInt(
            this.combat?.pressionAttaque?.bonusPourcentage,
            10,
        );
        const prochainRound = Number.parseInt(
            this.combat?.pressionAttaque?.prochainPalierRound,
            10,
        );

        if (!Number.isInteger(bonus) || bonus < 0) {
            this.pressionAttaqueTarget.hidden = true;

            return;
        }

        const prefixe = this.combat.numeroRound >= 10
            ? 'Pression maximale'
            : 'Pression du combat';
        const suite = Number.isInteger(prochainRound)
            ? ` Prochain palier au round ${prochainRound}.`
            : '';

        this.pressionAttaqueTarget.textContent =
            `${prefixe} : toutes les attaques gagnent +${bonus} %.${suite}`;
        this.pressionAttaqueTarget.hidden = false;
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

        if (this.combat.preparation?.active === true) {
            this.etatRoundTarget.textContent =
                'Le round commencera lorsque les deux joueurs seront prêts.';

            return;
        }

        if (
            this.combat.statut === 'termine'
            || this.combat.statut === 'abandonne'
            || this.combat.statut === 'forfait'
            || this.combat.statut === 'annule'
        ) {
            this.etatRoundTarget.textContent =
                'Le résultat final a été enregistré par le serveur.';

            return;
        }

        if (this.combat.planSoumis) {
            this.etatRoundTarget.textContent = this.combat.adversairePret
                ? 'Les deux plans sont prêts.'
                : 'Ton plan est envoyé. En attente du plan adverse.'
                    + this.texteDelaiPlan();

            return;
        }

        if (this.combat.adversairePret) {
            this.etatRoundTarget.textContent =
                'Ton adversaire a envoyé son plan.'
                + this.texteDelaiPlan(' Envoie le tien dans ');

            return;
        }

        this.etatRoundTarget.textContent =
            'Les deux joueurs sont prêts à préparer leur plan secret.';
    }

    texteDelaiPlan(prefixe = ' Forfait adverse possible dans ') {
        const expiration = Date.parse(this.combat?.expirationPlan ?? '');

        if (Number.isNaN(expiration)) {
            return '';
        }

        const secondes = Math.max(
            0,
            Math.ceil((expiration - Date.now()) / 1000),
        );
        const minutes = Math.floor(secondes / 60);
        const reste = secondes % 60;

        return `${prefixe}${minutes} min ${String(reste).padStart(2, '0')} s.`;
    }

    texteDelaiPreparation(prefixe) {
        const expiration = Date.parse(
            this.combat?.preparation?.expiration ?? ''
        );

        if (Number.isNaN(expiration)) {
            return '';
        }

        const secondes = Math.max(
            0,
            Math.ceil((expiration - Date.now()) / 1000),
        );
        const minutes = Math.floor(secondes / 60);
        const reste = secondes % 60;

        return `${prefixe}${minutes} min ${String(reste).padStart(2, '0')} s.`;
    }

    async animerNouveauRoundSiNecessaire() {
        const dernierRound = this.combat?.dernierRound;
        const numero = this.entierPositif(dernierRound?.numero);
        const combatId = this.entierPositif(this.combat?.combatId);

        if (numero === null || combatId === null) {
            return;
        }

        const cleMemoire = `stickverse:combat:${combatId}:round-anime`;
        let dernierNumeroAnime = 0;

        try {
            dernierNumeroAnime = Number.parseInt(
                window.sessionStorage.getItem(cleMemoire) ?? '0',
                10,
            );
        } catch {
            dernierNumeroAnime = 0;
        }

        if (Number.isInteger(dernierNumeroAnime) && dernierNumeroAnime >= numero) {
            return;
        }

        const lignes = this.lignesResultatRound(
            dernierRound?.resultats,
            dernierRound?.positionMoi,
        );
        const etapes = normaliserEtapesAnimation(
            lignes,
            dernierRound?.positionMoi,
        );

        if (etapes.length === 0) {
            this.memoriserRoundAnime(cleMemoire, numero);

            return;
        }

        this.animationRoundEnCours = true;
        this.annulerActualisation();
        this.planSectionTarget.hidden = true;
        this.combatActifTarget.setAttribute('aria-busy', 'true');
        const generation = ++this.generationAnimation;
        const mouvementReduit = window.matchMedia(
            '(prefers-reduced-motion: reduce)'
        ).matches;
        const durees = mouvementReduit
            ? { defense: 80, degats: 100, pause: 30 }
            : { defense: 1050, degats: 1350, pause: 420 };

        for (const etape of etapes) {
            if (generation !== this.generationAnimation) {
                return;
            }

            const carte = this.carteCombattant(etape.camp, etape.slot);

            if (!carte) {
                continue;
            }

            this.preparerCartePourAnimation(carte, etape);
            this.afficherPhaseAnimation(
                carte,
                'defense',
                `🛡 Protection : ${etape.bloque} point${etape.bloque > 1 ? 's' : ''} défendu${etape.bloque > 1 ? 's' : ''}`,
            );
            await this.attendreAnimation(durees.defense, generation);

            if (generation !== this.generationAnimation) {
                return;
            }

            this.appliquerDegatsAnimation(carte, etape);
            this.afficherPhaseAnimation(
                carte,
                'degats',
                etape.degats > 0
                    ? `⚔ Dégâts infligés : −${etape.degats} PV`
                    : '⚔ Dégâts infligés : 0',
            );
            await this.attendreAnimation(durees.degats, generation);
            this.masquerPhaseAnimation(carte);
            await this.attendreAnimation(durees.pause, generation);
        }

        if (generation !== this.generationAnimation) {
            return;
        }

        this.memoriserRoundAnime(cleMemoire, numero);
        this.animationRoundEnCours = false;
        this.combatActifTarget.removeAttribute('aria-busy');
        this.afficherFormulairePlan();
    }

    preparerCartePourAnimation(carte, etape) {
        const maximum = Number(carte.dataset.pvMaximum ?? 0);
        const barre = carte.querySelector('.carte-combattant-vie-remplissage');
        const valeur = carte.querySelector('.carte-combattant-vie-valeur');

        carte.classList.add('est-en-animation');

        if (barre && maximum > 0) {
            barre.style.width = `${Math.min(100, (etape.pvAvant / maximum) * 100)}%`;
        }

        if (valeur) {
            valeur.textContent = `PV ${etape.pvAvant} / ${maximum}`;
        }
    }

    appliquerDegatsAnimation(carte, etape) {
        const maximum = Number(carte.dataset.pvMaximum ?? 0);
        const barre = carte.querySelector('.carte-combattant-vie-remplissage');
        const valeur = carte.querySelector('.carte-combattant-vie-valeur');

        if (barre && maximum > 0) {
            barre.style.width = `${Math.min(100, (etape.pvRestants / maximum) * 100)}%`;
        }

        if (valeur) {
            valeur.textContent = `PV ${etape.pvRestants} / ${maximum}`;
        }
    }

    afficherPhaseAnimation(carte, phase, texte) {
        const panneau = carte.querySelector('.carte-combattant-animation');

        if (!panneau) {
            return;
        }

        panneau.dataset.phase = phase;
        panneau.textContent = texte;
        panneau.hidden = false;
    }

    masquerPhaseAnimation(carte) {
        const panneau = carte.querySelector('.carte-combattant-animation');

        if (panneau) {
            panneau.hidden = true;
            panneau.dataset.phase = '';
        }

        carte.classList.remove('est-en-animation');
    }

    attendreAnimation(duree, generation) {
        return new Promise((resolve) => {
            window.setTimeout(() => {
                resolve(generation === this.generationAnimation);
            }, duree);
        });
    }

    memoriserRoundAnime(cle, numero) {
        try {
            window.sessionStorage.setItem(cle, String(numero));
        } catch {
            // L’animation reste fonctionnelle si le stockage est bloqué.
        }
    }

    programmerActualisation() {
        this.annulerActualisation();

        if (
            this.actionEnCours
            || this.animationRoundEnCours
            || (
            this.combat?.statut !== 'en_attente'
            && this.combat?.statut !== 'en_cours'
            )
        ) {
            return;
        }

        const brouillonActif = this.planEstDisponible(this.combat)
            && (
                this.actionPlanActive !== 'cibleAttaqueX'
                || this.clesPlan().some(
                    (cle) => this.selectPlan(cle)?.value !== ''
                )
            );
        const delai = brouillonActif ? 7000 : 3000;

        this.minuterieActualisation = window.setTimeout(() => {
            if (this.combatActifIdCourant !== null) {
                if (
                    this.combat?.statut === 'en_attente'
                    && this.combat?.matchmaking?.active === true
                    && this.combat?.adversaire === null
                ) {
                    this.relancerRechercheAdversaire();

                    return;
                }

                this.chargerCombat(this.combatActifIdCourant);
            }
        }, delai);
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
            const nom = equipe.nom ?? `Équipe #${equipeId}`;
            const puissance = Number.isInteger(equipe.puissance)
                ? equipe.puissance
                : 0;
            option.textContent = `${nom} — puissance ${puissance}`;
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
        this.rechercherButtonTarget.disabled = aucuneEquipe;
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
        const nomEquipe = equipe.nom ?? `Équipe #${equipeId}`;
        titre.textContent = `${nomEquipe} — puissance ${equipe.puissance ?? 0}`;
        titre.className = 'selection-equipe-titre';
        this.equipeApercuTarget.append(titre);

        const groupes = document.createElement('div');
        groupes.className = 'selection-equipe-groupes';
        groupes.setAttribute('aria-label', 'Répartition de la composition');

        const groupeX = document.createElement('span');
        groupeX.className = 'selection-equipe-groupe selection-equipe-groupe-x';
        groupeX.textContent = 'Équipe X';

        const groupeY = document.createElement('span');
        groupeY.className = 'selection-equipe-groupe selection-equipe-groupe-y';
        groupeY.textContent = 'Équipe Y';

        groupes.append(groupeX, groupeY);
        this.equipeApercuTarget.append(groupes);

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

    lirePassifOuvert() {
        try {
            const valeur = JSON.parse(
                window.sessionStorage.getItem('stickverse.combat.passif-ouvert')
                ?? 'null'
            );

            if (
                !valeur
                || !Number.isInteger(Number(valeur.combatId))
                || !['moi', 'adversaire'].includes(valeur.camp)
                || !/^[A-D]$/.test(String(valeur.slot))
                || !/^\d+$/.test(String(valeur.index))
            ) {
                return null;
            }

            return {
                combatId: Number(valeur.combatId),
                camp: valeur.camp,
                slot: String(valeur.slot),
                index: String(valeur.index),
            };
        } catch {
            return null;
        }
    }

    enregistrerPassifOuvert(carte, index) {
        if (
            !this.combatActifIdCourant
            || !['moi', 'adversaire'].includes(carte.dataset.camp)
        ) {
            return;
        }

        this.passifOuvert = {
            combatId: this.combatActifIdCourant,
            camp: carte.dataset.camp,
            slot: carte.dataset.slot ?? '',
            index: String(index),
        };

        try {
            window.sessionStorage.setItem(
                'stickverse.combat.passif-ouvert',
                JSON.stringify(this.passifOuvert),
            );
        } catch {
            // Le panneau reste utilisable si le stockage de session est bloqué.
        }
    }

    effacerPassifOuvert() {
        this.passifOuvert = null;

        try {
            window.sessionStorage.removeItem('stickverse.combat.passif-ouvert');
        } catch {
            // Rien à faire : l’état local est déjà réinitialisé.
        }
    }

    sauvegarderPassifOuvert() {
        const bouton = this.participantsTarget?.querySelector(
            '.carte-combattant-passif[aria-expanded="true"]'
        );
        const carte = bouton?.closest('.carte-combattant');

        if (!bouton || !carte) {
            return;
        }

        this.enregistrerPassifOuvert(carte, bouton.dataset.passifIndex);
    }

    fermerPassifIncompatible() {
        if (
            !this.passifOuvert
            || !this.clesPlan().includes(this.actionPlanActive)
        ) {
            return;
        }

        if (
            this.passifOuvert.camp
            !== this.campPourActionPlan(this.actionPlanActive)
        ) {
            this.effacerPassifOuvert();
        }
    }

    restaurerPassifOuvert() {
        const ouvert = this.passifOuvert;

        if (
            !ouvert
            || ouvert.combatId !== this.combatActifIdCourant
        ) {
            return;
        }

        if (
            !this.clesPlan().includes(this.actionPlanActive)
            || ouvert.camp !== this.campPourActionPlan(this.actionPlanActive)
        ) {
            this.effacerPassifOuvert();

            return;
        }

        const carte = this.carteCombattant(ouvert.camp, ouvert.slot);
        const bouton = carte?.querySelector(
            `.carte-combattant-passif[data-passif-index="${ouvert.index}"]`
        );

        if (bouton) {
            bouton.click();
        }
    }

    creerCarteCombattant(combattant, pourcentagePrecedent = null) {
        const carte = document.createElement('article');
        const choixPlan = document.createElement('div');
        const menace = document.createElement('div');
        const animation = document.createElement('div');
        const previsualisation = document.createElement('div');
        const titre = document.createElement('h4');
        const image = document.createElement('img');
        const vie = document.createElement('div');
        const vieInformations = document.createElement('div');
        const vieValeur = document.createElement('span');
        const vieEtat = document.createElement('span');
        const vieBarre = document.createElement('div');
        const vieRemplissage = document.createElement('span');
        const viePrevisualisation = document.createElement('span');
        const passifs = document.createElement('div');
        const passifDetails = document.createElement('div');
        const statistiques = document.createElement('p');

        carte.className = 'carte-combattant';
        choixPlan.className = 'carte-combattant-choix';
        menace.className = 'carte-combattant-menace';
        animation.className = 'carte-combattant-animation';
        previsualisation.className = 'carte-combattant-preview-degats';
        menace.hidden = true;
        animation.hidden = true;
        previsualisation.hidden = true;
        animation.setAttribute('role', 'status');
        titre.className = 'carte-combattant-titre';
        image.className = 'carte-combattant-image';
        vie.className = 'carte-combattant-vie';
        vieInformations.className = 'carte-combattant-vie-informations';
        vieValeur.className = 'carte-combattant-vie-valeur';
        vieEtat.className = 'carte-combattant-vie-etat';
        vieBarre.className = 'carte-combattant-vie-barre';
        vieRemplissage.className = 'carte-combattant-vie-remplissage';
        viePrevisualisation.className = 'carte-combattant-vie-preview';
        viePrevisualisation.hidden = true;
        passifs.className = 'carte-combattant-passifs';
        passifs.setAttribute('aria-label', 'Emplacements de passifs');
        passifDetails.className = 'carte-combattant-passif-details';
        passifDetails.id = `combat-passif-details-${++this.passifDetailsCompteur}`;
        passifDetails.setAttribute('role', 'status');
        passifDetails.setAttribute('aria-live', 'polite');
        passifDetails.hidden = true;

        // Les emplacements restent prêts pour les futurs passifs, mais une
        // carte sans passif ne doit afficher aucun carré.
        const passifsCarte = Array.isArray(combattant.passifs)
            ? combattant.passifs.slice(0, 6)
            : [];

        for (const [index, passif] of passifsCarte.entries()) {
            const estPassifLisible = passif && typeof passif === 'object';
            const emplacement = document.createElement(
                estPassifLisible ? 'button' : 'span'
            );
            emplacement.className = 'carte-combattant-passif';
            emplacement.dataset.passifIndex = String(index + 1);
            if (estPassifLisible) {
                const nom = typeof passif.nom === 'string' && passif.nom.trim() !== ''
                    ? passif.nom.trim()
                    : 'Passif';
                const description = typeof passif.description === 'string'
                    ? passif.description.trim()
                    : '';
                const libelle = description ? `${nom} — ${description}` : nom;
                emplacement.type = 'button';
                emplacement.textContent = nom.charAt(0).toUpperCase();
                emplacement.title = libelle;
                emplacement.setAttribute('aria-label', libelle);
                emplacement.setAttribute('aria-controls', passifDetails.id);
                emplacement.setAttribute('aria-expanded', 'false');
                emplacement.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    const estOuvert = !passifDetails.hidden
                        && passifDetails.dataset.passifIndex
                            === emplacement.dataset.passifIndex;

                    if (estOuvert) {
                        passifDetails.hidden = true;
                        passifDetails.textContent = '';
                        delete passifDetails.dataset.passifIndex;
                        emplacement.setAttribute('aria-expanded', 'false');
                        this.effacerPassifOuvert();

                        return;
                    }

                    passifDetails.textContent = libelle;
                    passifDetails.dataset.passifIndex = emplacement.dataset.passifIndex;
                    passifDetails.hidden = false;

                    passifs.querySelectorAll('.carte-combattant-passif[aria-expanded="true"]')
                        .forEach((bouton) => bouton.setAttribute('aria-expanded', 'false'));
                    emplacement.setAttribute('aria-expanded', 'true');
                    this.enregistrerPassifOuvert(
                        carte,
                        emplacement.dataset.passifIndex,
                    );
                });
                emplacement.addEventListener('keydown', (event) => {
                    event.stopPropagation();

                    if (event.key !== 'Escape') {
                        return;
                    }

                    passifDetails.hidden = true;
                    passifDetails.textContent = '';
                    delete passifDetails.dataset.passifIndex;
                    emplacement.setAttribute('aria-expanded', 'false');
                    this.effacerPassifOuvert();
                });
            } else {
                emplacement.setAttribute('aria-hidden', 'true');
            }
            passifs.append(emplacement);
        }

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
        const rareteBrute = Number(combattant.rarete ?? 1);
        const rarete = Number.isInteger(rareteBrute)
            && rareteBrute >= 1
            && rareteBrute <= 5
            ? rareteBrute
            : 1;
        carte.dataset.rarete = String(rarete);

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
        carte.dataset.pvActuels = String(pvActuels);
        carte.dataset.pvMaximum = String(pvMaximum);
        carte.dataset.pourcentageVie = String(pourcentageVie);
        carte.dataset.attaque = String(combattant.attaque ?? 0);
        carte.dataset.defense = String(combattant.defense ?? 0);
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
        vieBarre.append(vieRemplissage, viePrevisualisation);
        vie.append(vieInformations, vieBarre);

        const statistiquesCarte = [
            `ATQ ${combattant.attaque ?? '—'}`,
            `DÉF ${combattant.defense ?? '—'}`,
        ];

        if (Number.isInteger(combattant.puissance)) {
            statistiquesCarte.push(`PUI ${combattant.puissance}`);
        }

        statistiques.textContent = statistiquesCarte.join(' · ');

        carte.append(
            choixPlan,
            menace,
            animation,
            previsualisation,
            titre,
            image,
            vie,
            passifs,
            passifDetails,
            statistiques,
        );

        return carte;
    }

    carteCombattant(camp, slot) {
        return Array.from(
            this.participantsTarget.querySelectorAll(
                '.carte-combattant[data-camp][data-slot]'
            )
        ).find(
            (carte) => carte.dataset.camp === camp
                && carte.dataset.slot === slot
        ) ?? null;
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
            victoire_forfait: 'Victoire par forfait',
            forfait: 'Défaite par forfait',
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
                combat.adversairePseudo ?? 'inconnu',
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

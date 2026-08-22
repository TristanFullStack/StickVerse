import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'chargement',
        'erreur',
        'salon',
        'combatActif',
        'combatActifId',
        'combatStatut',
        'numeroRound',
        'etatRound',
        'finCombat',
        'finCombatTitre',
        'finCombatMessage',
        'resultatRound',
        'resultatRoundNumero',
        'resultatRoundLignes',
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
        'equipeSelect',
        'equipeApercu',
        'creerButton',
        'aucunCombat',
        'combatsDisponibles',
    ];

    static values = {
        salonUrl: String,
        creerUrl: String,
        rejoindreUrlModele: String,
        combatUrlModele: String,
        planUrlModele: String,
        abandonUrlModele: String,
        imagesBaseUrl: String,
    };

    connect() {
        this.salon = null;
        this.combat = null;
        this.combatActifIdCourant = null;
        this.requeteEnCours = null;
        this.minuterieActualisation = null;
        this.chargerSalon();
    }

    disconnect() {
        this.requeteEnCours?.abort();
        this.annulerActualisation();
    }

    rafraichir() {
        if (this.combatActifIdCourant !== null) {
            this.chargerCombat(this.combatActifIdCourant);

            return;
        }

        this.chargerSalon();
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
                    erreur.message
                    ?? 'Impossible de charger le salon.'
                );
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

            this.combat = donnees;
            this.afficherCombat();
            this.programmerActualisation();
        } catch (erreur) {
            if (erreur.name !== 'AbortError') {
                this.afficherErreur(
                    erreur.message
                    ?? 'Impossible de charger le combat.'
                );
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
        this.afficherFormulairePlan();
        this.afficherBoutonAbandon();
    }

    afficherBoutonAbandon() {
        this.abandonButtonTarget.hidden = !(
            this.combat.statut === 'en_cours'
            && this.combat.adversaire !== null
        );
    }

    afficherFinCombat() {
        const estTermine = this.combat.statut === 'termine'
            || this.combat.statut === 'abandonne';

        if (!estTermine) {
            this.finCombatTarget.hidden = true;
            this.finCombatTarget.dataset.resultat = '';

            return;
        }

        const moiId = this.combat.moi?.id;
        const gagnantId = this.combat.gagnantId;
        const victoire = gagnantId !== null && gagnantId === moiId;
        let titre;
        let message;
        let resultat;

        if (this.combat.statut === 'abandonne') {
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
        const lignes = Object.entries(resultats)
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
            listeTarget.append(this.creerCarteCombattant(combattant));
        }
    }

    afficherEtatRound() {
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
            this.combat?.statut !== 'en_attente'
            && this.combat?.statut !== 'en_cours'
        ) {
            return;
        }

        this.minuterieActualisation = window.setTimeout(() => {
            if (this.combatActifIdCourant !== null) {
                this.chargerCombat(this.combatActifIdCourant);
            }
        }, 3000);
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

    creerCarteCombattant(combattant) {
        const carte = document.createElement('article');
        const titre = document.createElement('h4');
        const image = document.createElement('img');
        const statistiques = document.createElement('p');

        carte.className = 'carte-combattant';
        titre.className = 'carte-combattant-titre';
        image.className = 'carte-combattant-image';
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

        if (combattant.vivant === false) {
            carte.classList.add('est-ko');
        }

        if (typeof combattant.image === 'string') {
            image.src = [
                this.imagesBaseUrlValue,
                encodeURIComponent(combattant.image),
            ].join('/');
        }

        const pvActuels = combattant.pvActuels
            ?? combattant.pv
            ?? '—';
        const pvMaximum = combattant.pvMaximum
            ?? combattant.pv
            ?? '—';

        statistiques.textContent = [
            `PV ${pvActuels} / ${pvMaximum}`,
            `ATQ ${combattant.attaque ?? '—'}`,
            `DÉF ${combattant.defense ?? '—'}`,
        ].join(' · ');

        carte.append(titre, image, statistiques);

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
        this.masquerErreur();
        this.basculerBoutons(true);

        try {
            await action();
        } catch (erreur) {
            this.afficherErreur(
                erreur.message
                ?? 'L’action demandée a échoué.'
            );
        } finally {
            this.basculerBoutons(false);
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
        const typeContenu = reponse.headers.get('content-type') ?? '';

        if (!typeContenu.includes('application/json')) {
            throw new Error('La réponse du serveur est invalide.');
        }

        return reponse.json();
    }

    basculerBoutons(desactiver) {
        for (const bouton of this.element.querySelectorAll('button')) {
            bouton.disabled = desactiver;
        }
    }

    afficherErreur(message) {
        this.erreurTarget.textContent = message;
        this.erreurTarget.hidden = false;
    }

    masquerErreur() {
        this.erreurTarget.textContent = '';
        this.erreurTarget.hidden = true;
    }
}

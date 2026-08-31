const SLOTS_PAR_GROUPE = Object.freeze({
    X: Object.freeze(['A', 'B']),
    Y: Object.freeze(['C', 'D']),
});

export function combattantsVivants(participant) {
    if (!Array.isArray(participant?.combattants)) {
        return [];
    }

    return participant.combattants.filter((combattant) => {
        const pv = Number(combattant?.pvActuels ?? combattant?.pv ?? 0);

        return combattant?.vivant !== false && Number.isFinite(pv) && pv > 0;
    });
}

export function puissanceGroupe(
    participant,
    groupe,
    statistique,
    bonusAttaque = 0,
) {
    const slots = SLOTS_PAR_GROUPE[groupe] ?? [];
    const total = combattantsVivants(participant)
        .filter((combattant) => slots.includes(combattant.slot))
        .reduce(
            (somme, combattant) => somme + nombre(combattant[statistique]),
            0,
        );

    return statistique === 'attaque'
        ? Math.round(total * (1 + (nombre(bonusAttaque) / 100)))
        : total;
}

export function capacitesTactiques(participant, bonusAttaque = 0) {
    const attaqueX = puissanceGroupe(
        participant,
        'X',
        'attaque',
        bonusAttaque,
    );
    const attaqueY = puissanceGroupe(
        participant,
        'Y',
        'attaque',
        bonusAttaque,
    );
    const defenseX = puissanceGroupe(participant, 'X', 'defense');
    const defenseY = puissanceGroupe(participant, 'Y', 'defense');

    return {
        attaqueX,
        attaqueY,
        focus: attaqueX + attaqueY,
        defenseX,
        defenseY,
        doubleDefense: defenseX + defenseY,
    };
}

export function calculerMenaceFocus(
    combattant,
    attaquant,
    defenseur,
    bonusAttaque = 0,
) {
    const pv = nombre(combattant?.pvActuels ?? combattant?.pv);

    if (combattant?.vivant === false || pv <= 0) {
        return null;
    }

    const attaque = capacitesTactiques(participantValide(attaquant), bonusAttaque);
    const defense = capacitesTactiques(defenseur);
    const defenses = [defense.defenseX, defense.defenseY]
        .sort((a, b) => b - a);
    const degatsSansDefense = Math.max(0, attaque.focus);
    const degatsUneDefense = Math.max(0, attaque.focus - (defenses[0] ?? 0));
    const degatsDoubleDefense = Math.max(
        0,
        attaque.focus - defense.doubleDefense,
    );

    if (degatsSansDefense < pv) {
        return null;
    }

    if (degatsDoubleDefense >= pv) {
        return {
            niveau: 'grave',
            symbole: '☠',
            texte: `FOCUS ${attaque.focus} · DOUBLE DÉFENSE INSUFFISANTE`,
        };
    }

    if (degatsUneDefense >= pv) {
        return {
            niveau: 'danger',
            symbole: '!',
            texte: `FOCUS ${attaque.focus} · DOUBLE DÉFENSE REQUISE`,
        };
    }

    return {
        niveau: 'danger',
        symbole: '!',
        texte: `FOCUS ${attaque.focus} · DÉFENSE REQUISE`,
    };
}

export function calculerApercuDegats(attaque, pvActuels, pvMaximum) {
    const pv = Math.max(0, nombre(pvActuels));
    const maximum = Math.max(0, nombre(pvMaximum));
    const degats = Math.min(pv, Math.max(0, nombre(attaque)));

    return {
        degats,
        pvRestants: Math.max(0, pv - degats),
        pourcentageDegats: maximum > 0
            ? Math.min(100, (degats / maximum) * 100)
            : 0,
        pourcentageRestant: maximum > 0
            ? Math.min(100, (Math.max(0, pv - degats) / maximum) * 100)
            : 0,
    };
}

export function normaliserEtapesAnimation(lignes, positionMoi) {
    return lignes
        .filter((ligne) => ligne?.resultat && ligne?.slot)
        .map((ligne) => ({
            camp: ligne.proprietaire === positionMoi
                ? 'moi'
                : 'adversaire',
            slot: ligne.slot,
            attaque: nombre(ligne.resultat.attaque),
            defense: nombre(ligne.resultat.defense),
            bloque: Math.min(
                nombre(ligne.resultat.attaque),
                nombre(ligne.resultat.defense),
            ),
            degats: nombre(ligne.resultat.degatsEffectifs),
            pvAvant: nombre(ligne.resultat.pvAvant),
            pvRestants: nombre(ligne.resultat.pvRestants),
        }));
}

function participantValide(participant) {
    return participant ?? { combattants: [] };
}

function nombre(valeur) {
    const resultat = Number(valeur);

    return Number.isFinite(resultat) ? Math.max(0, resultat) : 0;
}

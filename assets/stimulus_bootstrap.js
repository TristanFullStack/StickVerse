import { Application } from '@hotwired/stimulus';
import CaisseOuvertureController from './controllers/caisse_ouverture_controller.js';
import CombatEnLigneController from './controllers/combat_en_ligne_controller.js';
import HelloController from './controllers/hello_controller.js';
import InventorySaleController from './controllers/inventory_sale_controller.js';
import ShopController from './controllers/shop_controller.js';
import TeamComposerController from './controllers/team_composer_controller.js';

// Les contrôleurs métier sont enregistrés explicitement. Cela garantit que le
// bouton « Ouvrir » intercepte bien le formulaire même si le fichier généré
// par Stimulus/AssetMapper est encore en cache après un déploiement.
const app = Application.start();
app.register('caisse-ouverture', CaisseOuvertureController);
app.register('combat-en-ligne', CombatEnLigneController);
app.register('hello', HelloController);
app.register('inventory-sale', InventorySaleController);
app.register('shop', ShopController);
app.register('team-composer', TeamComposerController);
